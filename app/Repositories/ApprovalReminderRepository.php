<?php

namespace App\Repositories;

use App\ApprovalReminder;
use App\ApprovalReminderLog;
use App\Reimbursement;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class ApprovalReminderRepository
{
    public const WORKFLOW_CODE_REIMBURSEMENT = 'reimbursement';

    public function initialDelayMinutes(): int
    {
        return (int) config('approval_reminder.initial_delay_minutes', 30);
    }

    public function repeatIntervalMinutes(): int
    {
        return (int) config('approval_reminder.repeat_interval_minutes', 30);
    }

    public function maxDurationMinutes(): int
    {
        return (int) config('approval_reminder.max_duration_minutes', 720);
    }

    public function upsertFromReimbursement(Reimbursement $reimbursement, ?string $baseUrl = null): ApprovalReminder
    {
        $now = Carbon::now();
        $pending = $this->isPendingReimbursement($reimbursement);
        $reminder = ApprovalReminder::firstOrNew([
            'subject_type' => Reimbursement::class,
            'subject_id' => $reimbursement->id,
            'workflow_code' => self::WORKFLOW_CODE_REIMBURSEMENT,
        ]);

        if (!$pending) {
            if ($reminder->exists && $reminder->is_active) {
                $this->stop($reminder, $this->reasonForStatus((int) $reimbursement->status), (int) $reimbursement->status);
            }

            return $reminder;
        }

        $payload = $this->buildPayload($reimbursement, $baseUrl);
        $currentStage = (string) $reimbursement->status;
        $previousStage = $reminder->exists ? (string) $reminder->stage_code : null;
        $stageChanged = $reminder->exists && $previousStage !== null && $previousStage !== $currentStage;
        $isNewCycle = !$reminder->exists || !$reminder->is_active || !$reminder->first_due_at || $stageChanged;

        if ($isNewCycle) {
            $referenceAt = $stageChanged ? $now : Carbon::parse($reimbursement->created_at);
            $reminder->first_due_at = $referenceAt->copy()->addMinutes($this->initialDelayMinutes());
            $reminder->next_send_at = $referenceAt->copy()->addMinutes($this->initialDelayMinutes());
            $reminder->expires_at = $referenceAt->copy()->addMinutes($this->maxDurationMinutes());
            $reminder->last_sent_at = null;
            $reminder->last_attempt_at = null;
            $reminder->send_count = 0;
            $reminder->is_active = true;
            $reminder->stopped_at = null;
            $reminder->stopped_reason = null;
            $reminder->last_error = null;
        }

        $reminder->fill([
            'subject_type' => Reimbursement::class,
            'subject_id' => $reimbursement->id,
            'workflow_code' => self::WORKFLOW_CODE_REIMBURSEMENT,
            'source_status' => (int) $reimbursement->status,
            'stage_code' => (string) $reimbursement->status,
            'stage_label' => $payload['stage_label'],
            'recipient_name' => $payload['recipient_name'],
            'recipient_phone' => $payload['recipient_phone'],
            'metadata' => $payload['metadata'],
        ]);

        $reminder->save();

        return $reminder;
    }

    public function stop(ApprovalReminder $reminder, string $reason, ?int $sourceStatus = null): ApprovalReminder
    {
        $now = Carbon::now();

        $reminder->is_active = false;
        $reminder->stopped_reason = $reason;
        $reminder->stopped_at = $now;
        $reminder->last_error = null;

        if ($sourceStatus !== null) {
            $reminder->source_status = $sourceStatus;
        }

        $reminder->save();

        return $reminder;
    }

    public function dueReminders(Carbon $now)
    {
        return ApprovalReminder::query()
            ->where('workflow_code', self::WORKFLOW_CODE_REIMBURSEMENT)
            ->where('is_active', true)
            ->where('next_send_at', '<=', $now)
            ->where('expires_at', '>', $now)
            ->orderBy('next_send_at')
            ->orderBy('id')
            ->get();
    }

    public function activeReminders()
    {
        return ApprovalReminder::query()
            ->where('workflow_code', self::WORKFLOW_CODE_REIMBURSEMENT)
            ->where('is_active', true)
            ->orderBy('id');
    }

    public function findReminder(int $reminderId)
    {
        return ApprovalReminder::find($reminderId);
    }

    public function claimDispatch(ApprovalReminder $reminder, Carbon $scheduledFor): ?ApprovalReminderLog
    {
        try {
            return ApprovalReminderLog::create([
                'approval_reminder_id' => $reminder->id,
                'scheduled_for' => $scheduledFor,
                'status' => 'queued',
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyException($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    public function markLogProcessing(ApprovalReminderLog $log): ApprovalReminderLog
    {
        $log->status = 'processing';
        $log->save();

        return $log;
    }

    public function markLogSent(ApprovalReminderLog $log, array $response, int $recipientCount): ApprovalReminderLog
    {
        $now = Carbon::now();
        $log->status = 'sent';
        $log->sent_at = $now;
        $log->provider_response = $response;
        $log->recipient_count = $recipientCount;
        $log->error_message = null;
        $log->save();

        return $log;
    }

    public function markLogFailed(ApprovalReminderLog $log, string $message, array $response = [])
    {
        $log->status = 'failed';
        $log->sent_at = Carbon::now();
        $log->provider_response = $response;
        $log->error_message = $message;
        $log->save();

        return $log;
    }

    public function refreshAfterSuccess(ApprovalReminder $reminder, ?Carbon $sentAt = null): ApprovalReminder
    {
        $sentAt = $sentAt ?: Carbon::now();
        $reminder->last_sent_at = $sentAt;
        $reminder->last_attempt_at = $sentAt;
        $reminder->send_count = (int) $reminder->send_count + 1;
        $reminder->next_send_at = $sentAt->copy()->addMinutes($this->repeatIntervalMinutes());
        $reminder->last_error = null;
        $reminder->save();

        return $reminder;
    }

    public function refreshAfterFailure(ApprovalReminder $reminder, ?Carbon $failedAt = null, ?string $errorMessage = null): ApprovalReminder
    {
        $failedAt = $failedAt ?: Carbon::now();
        $reminder->last_attempt_at = $failedAt;
        $reminder->next_send_at = $failedAt->copy()->addMinutes($this->repeatIntervalMinutes());
        $reminder->last_error = $errorMessage;
        $reminder->save();

        return $reminder;
    }

    public function isPendingReimbursement(Reimbursement $reimbursement): bool
    {
        return in_array((int) $reimbursement->status, [0, 1, 2, 11], true);
    }

    public function buildPayload(Reimbursement $reimbursement, ?string $baseUrl = null): array
    {
        $stageLabel = $this->stageLabel((int) $reimbursement->status);
        $recipient = $this->recipientForReimbursement($reimbursement);
        $detailUrl = $this->detailUrl($reimbursement, $baseUrl);

        return [
            'stage_label' => $stageLabel,
            'recipient_name' => $recipient ? $recipient->name : null,
            'recipient_phone' => $recipient ? $recipient->phoneNumber : null,
            'metadata' => [
                'no_reimbursement' => $reimbursement->no_reimbursement,
                'nominal_pengajuan' => (float) $reimbursement->nominal_pengajuan,
                'detail_url' => $detailUrl,
                'reimbursement_type' => (int) $reimbursement->reimbursement_type,
                'stage_label' => $stageLabel,
            ],
        ];
    }

    public function stageLabel(int $status): string
    {
        if ($status === 0) {
            return 'Head Department';
        }

        if ($status === 1) {
            return 'HR GA';
        }

        if ($status === 2) {
            return 'Finance Supervisor / Owner';
        }

        if ($status === 11) {
            return 'Finance Manager / Owner';
        }

        return 'Approval';
    }

    public function detailUrl(Reimbursement $reimbursement, ?string $baseUrl = null): string
    {
        $path = '/reimbursement-travel/' . $reimbursement->id;

        if ((int) $reimbursement->reimbursement_type === 1) {
            $path = '/reimbursement-driver/' . $reimbursement->id;
        }

        if ((int) $reimbursement->reimbursement_type === 3) {
            $path = '/reimbursement-entertaiment/' . $reimbursement->id;
        }

        if ($baseUrl !== null) {
            return rtrim($baseUrl, '/') . $path;
        }

        return url($path);
    }

    public function recipientForReimbursement(Reimbursement $reimbursement)
    {
        $recipients = $this->recipientCollectionForReimbursement($reimbursement);
        return $recipients->first();
    }

    public function approverJabatanForStage(int $reimbursementType, int $status): array
    {
        if ($status === 1) {
            return $reimbursementType === 2
                ? ['Finance', 'Finance Supervisor', 'HR', 'HR GA']
                : ['Finance', 'HR GA'];
        }

        if ($status === 2) {
            return ['Finance Supervisor', 'Owner'];
        }

        if ($status === 11) {
            return ['Finance Manager', 'Owner'];
        }

        return [];
    }

    public function recipientCollectionForReimbursement(Reimbursement $reimbursement)
    {
        $status = (int) $reimbursement->status;

        if ($status === 0) {
            return $this->headDepartmentRecipients($reimbursement);
        }

        $jabatan = $this->approverJabatanForStage((int) $reimbursement->reimbursement_type, $status);

        if ($jabatan === []) {
            return collect();
        }

        return $this->usersWithJabatan($jabatan);
    }

    private function headDepartmentRecipients(Reimbursement $reimbursement)
    {
        $submitter = User::find($reimbursement->id_user);

        if (!$submitter || empty($submitter->id_approval)) {
            return collect();
        }

        $approver = User::find($submitter->id_approval);

        if (!$approver || empty($approver->phoneNumber)) {
            return collect();
        }

        return collect([$approver]);
    }

    private function usersWithJabatan(array $jabatan)
    {
        return User::whereIn('jabatan', $jabatan)
            ->whereNotNull('phoneNumber')
            ->where('phoneNumber', '!=', '')
            ->get(['name', 'phoneNumber'])
            ->unique('phoneNumber')
            ->values();
    }

    public function shouldStopForStatus(int $status): bool
    {
        return !in_array($status, [0, 1, 2, 11], true);
    }

    public function reasonForStatus(int $status): string
    {
        if ($status === 4 || $status === 9) {
            return 'rejected';
        }

        if ($status === 5) {
            return 'settled';
        }

        return 'closed';
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $code = (string) $exception->getCode();

        return in_array($code, ['23000', '23505'], true);
    }
}