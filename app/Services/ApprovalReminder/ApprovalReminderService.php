<?php

namespace App\Services\ApprovalReminder;

use App\ApprovalReminder;
use App\ApprovalReminderLog;
use App\Jobs\SendApprovalReminderJob;
use App\Repositories\ApprovalReminderRepository;
use App\Reimbursement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApprovalReminderService
{
    private $repository;

    private $client;

    public function __construct(ApprovalReminderRepository $repository, FonnteClient $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public function processReimbursementReminders(bool $dryRun = false, ?string $baseUrl = null): array
    {
        $this->syncPendingReimbursements($baseUrl);
        $this->reconcileActiveReimbursements($baseUrl);

        if ($dryRun) {
            return $this->previewDueReminders($baseUrl);
        }

        return $this->queueDueReminders();
    }

    public function syncPendingReimbursements(?string $baseUrl = null): int
    {
        $count = 0;

        Reimbursement::query()
            ->whereIn('status', [0, 1, 2, 11])
            ->orderBy('id')
            ->chunkById(100, function ($reimbursements) use (&$count, $baseUrl) {
                foreach ($reimbursements as $reimbursement) {
                    $this->repository->upsertFromReimbursement($reimbursement, $baseUrl);
                    $count++;
                }
            });

        return $count;
    }

    public function reconcileActiveReimbursements(?string $baseUrl = null): int
    {
        $count = 0;
        $now = Carbon::now();

        $this->repository->activeReminders()->chunkById(100, function ($reminders) use (&$count, $now, $baseUrl) {
            foreach ($reminders as $reminder) {
                $reimbursement = Reimbursement::find($reminder->subject_id);

                if (!$reimbursement) {
                    $this->repository->stop($reminder, 'source_missing', null);
                    $count++;
                    continue;
                }

                if (!$this->repository->isPendingReimbursement($reimbursement)) {
                    $this->repository->stop($reminder, $this->repository->reasonForStatus((int) $reimbursement->status), (int) $reimbursement->status);
                    $count++;
                    continue;
                }

                if ($now->greaterThanOrEqualTo(Carbon::parse($reimbursement->created_at)->copy()->addMinutes($this->repository->maxDurationMinutes()))) {
                    $this->repository->stop($reminder, 'expired', (int) $reimbursement->status);
                    $count++;
                    continue;
                }

                $this->repository->upsertFromReimbursement($reimbursement, $baseUrl);
                $count++;
            }
        });

        return $count;
    }

    public function previewDueReminders(?string $baseUrl = null): array
    {
        $now = Carbon::now();
        $dueReminders = $this->repository->dueReminders($now);
        $output = [];

        foreach ($dueReminders as $reminder) {
            $reimbursement = Reimbursement::find($reminder->subject_id);
            if (!$reimbursement) {
                continue;
            }

            $recipients = $this->repository->recipientCollectionForReimbursement($reimbursement);
            $recipientNames = $recipients->pluck('name')->filter()->implode(', ');

            $output[] = [
                'id' => $reminder->id,
                'no_reimbursement' => $reimbursement->no_reimbursement,
                'stage_label' => $reminder->stage_label,
                'recipients' => $recipientNames,
                'next_send_at' => (string) $reminder->next_send_at,
                'expires_at' => (string) $reminder->expires_at,
                'detail_url' => $this->repository->detailUrl($reimbursement, $baseUrl),
            ];
        }

        return $output;
    }

    public function queueDueReminders(): array
    {
        $now = Carbon::now();
        $queued = 0;
        $skipped = 0;

        $this->repository->dueReminders($now)->each(function (ApprovalReminder $reminder) use (&$queued, &$skipped, $now) {
            $log = $this->repository->claimDispatch($reminder, $reminder->next_send_at);

            if ($log === null) {
                $skipped++;
                return;
            }

            SendApprovalReminderJob::dispatch($reminder->id, $log->id);
            $queued++;
        });

        return [
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }

    public function deliverReminder(int $reminderId, int $logId): void
    {
        $reminder = $this->repository->findReminder($reminderId);
        $log = ApprovalReminderLog::find($logId);

        if (!$reminder || !$log) {
            return;
        }

        $reimbursement = Reimbursement::find($reminder->subject_id);
        if (!$reimbursement) {
            $this->repository->stop($reminder, 'source_missing', null);
            $this->repository->markLogFailed($log, 'Source reimbursement not found');
            return;
        }

        if ($this->repository->shouldStopForStatus((int) $reimbursement->status)) {
            $this->repository->stop($reminder, $this->repository->reasonForStatus((int) $reimbursement->status), (int) $reimbursement->status);
            $this->repository->markLogFailed($log, 'Reminder stopped because approval is no longer pending');
            return;
        }

        $now = Carbon::now();
        if ($now->greaterThan(Carbon::parse($reminder->expires_at))) {
            $this->repository->stop($reminder, 'expired', (int) $reimbursement->status);
            $this->repository->markLogFailed($log, 'Reminder expired after ' . $this->repository->maxDurationMinutes() . ' minutes');
            return;
        }

        $recipients = $this->repository->recipientCollectionForReimbursement($reimbursement);
        if ($recipients->isEmpty()) {
            $this->repository->markLogFailed($log, 'No recipients available for this approval reminder');
            $this->repository->refreshAfterFailure($reminder, $now, 'No recipients available for this approval reminder');
            Log::warning('Approval reminder skipped because there is no recipient', [
                'reminder_id' => $reminder->id,
                'reimbursement_id' => $reimbursement->id,
            ]);
            return;
        }

        $this->repository->markLogProcessing($log);

        $responses = [];
        foreach ($recipients as $recipient) {
            if (empty($recipient->phoneNumber)) {
                continue;
            }

            $message = $this->buildMessage($reimbursement, $recipient, $reminder, $this->repository->detailUrl($reimbursement, config('app.url')));
            $responses[] = [
                'recipient' => $recipient->name,
                'phone' => $recipient->phoneNumber,
                'response' => $this->client->send($recipient->phoneNumber, $message),
            ];
        }

        if (empty($responses)) {
            $this->repository->markLogFailed($log, 'No recipient with phone number was available');
            $this->repository->refreshAfterFailure($reminder, $now, 'No recipient with phone number was available');
            return;
        }

        $this->repository->markLogSent($log, $responses, count($responses));
        $this->repository->refreshAfterSuccess($reminder, $now);

        Log::info('Approval reminder sent', [
            'reminder_id' => $reminder->id,
            'reimbursement_id' => $reimbursement->id,
            'recipients' => count($responses),
        ]);
    }

    public function failReminder(int $reminderId, int $logId, string $message): void
    {
        $reminder = $this->repository->findReminder($reminderId);
        $log = ApprovalReminderLog::find($logId);

        if (!$reminder || !$log) {
            return;
        }

        $this->repository->markLogFailed($log, $message);
        $this->repository->refreshAfterFailure($reminder, Carbon::now(), $message);

        Log::error('Approval reminder job failed', [
            'reminder_id' => $reminderId,
            'log_id' => $logId,
            'message' => $message,
        ]);
    }

    private function buildMessage(Reimbursement $reimbursement, $recipient, ApprovalReminder $reminder, string $detailUrl): string
    {
        return 'Hai *' . $recipient->name . "*,\n\n" .
            'Pengajuan reimbursement nomor *' . $reimbursement->no_reimbursement . '* sebesar *Rp ' . number_format($reimbursement->nominal_pengajuan, 0, ',', '.') . '* masih berstatus *PENDING* pada tahap *' . $reminder->stage_label . "*.\n\n" .
            'Notifikasi reminder akan terkirim otomatis selama pengajuan belum Anda approve.' . "\n" .
            'Pengingat pertama sekitar *' . $this->intervalText($this->repository->initialDelayMinutes()) . '* setelah pengajuan, lalu diulang setiap *' . $this->intervalText($this->repository->repeatIntervalMinutes()) . '*. Pengingat berhenti setelah *' . $this->intervalText($this->repository->maxDurationMinutes()) . '* atau saat status berubah menjadi *APPROVED/REJECTED*.' . "\n\n" .
            'Terima kasih.' . "\n\n" .
            'Klik untuk melihat detail pengajuan : ' . $detailUrl;
    }

    private function intervalText(int $minutes): string
    {
        if ($minutes % 60 === 0) {
            $hours = (int) ($minutes / 60);

            return $hours . ' jam';
        }

        return $minutes . ' menit';
    }
}