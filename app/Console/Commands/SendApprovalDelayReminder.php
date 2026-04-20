<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendApprovalDelayReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursement:send-approval-delay-reminder {--dry-run : Show which reminders would be sent without sending WhatsApp messages} {--base-url= : Override app base URL for detail links, e.g. https://uudp.sf-indonesia.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WA reminders for reimbursement approvals delayed more than 5 minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $threshold = Carbon::now()->subHours(12);
        $dryRun = (bool) $this->option('dry-run');
        $baseUrlOption = $this->option('base-url');
        $baseUrl = is_string($baseUrlOption) && trim($baseUrlOption) !== ''
            ? rtrim(trim($baseUrlOption), '/')
            : null;

        $reimbursements = Reimbursement::query()
            ->whereIn('reimbursement_type', [1, 2, 3])
            ->whereIn('status', [0, 1, 2])
            ->where('updated_at', '<=', $threshold)
            ->orderBy('id')
            ->get([
                'id',
                'id_user',
                'status',
                'no_reimbursement',
                'nominal_pengajuan',
                'created_by',
                'reimbursement_type',
                'updated_at',
            ]);

        $sentCount = 0;

        foreach ($reimbursements as $reimbursement) {
            if ($this->reminderAlreadySent($reimbursement)) {
                continue;
            }

            $recipients = $this->resolveRecipients($reimbursement);
            if ($recipients->isEmpty()) {
                continue;
            }

            $stageLabel = $this->stageLabel($reimbursement->status);
            $detailUrl = $this->detailUrl($reimbursement, $baseUrl);
            $sentAt = Carbon::now()->toDateTimeString();
            $statusUpdatedAt = Carbon::parse($reimbursement->updated_at)->toDateTimeString();
            $hasSent = false;

            foreach ($recipients as $recipient) {
                if (empty($recipient->phoneNumber)) {
                    continue;
                }

                $message = $this->buildMessage($reimbursement, $recipient, $stageLabel, $detailUrl);
                if ($dryRun) {
                    $this->line('[DRY RUN] Would send to ' . $recipient->phoneNumber . ' for reimbursement ' . $reimbursement->no_reimbursement);
                    $this->line($message);
                } else {
                    $this->sendWhatsapp($recipient->phoneNumber, $message);
                }
                $sentCount++;
                $hasSent = true;
            }

            if ($hasSent && !$dryRun) {
                DB::table('reimbursement_reminder_logs')->insert([
                    'reimbursement_id' => $reimbursement->id,
                    'reimbursement_status' => $reimbursement->status,
                    'sent_for_updated_at' => $statusUpdatedAt,
                    'sent_at' => $sentAt,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);
            }
        }

        $this->info($dryRun ? 'Dry run complete: ' . $sentCount . ' reminder(s) would be sent' : 'Reminder sent: ' . $sentCount);

        return 0;
    }

    private function reminderAlreadySent($reimbursement): bool
    {
        return DB::table('reimbursement_reminder_logs')
            ->where('reimbursement_id', $reimbursement->id)
            ->where('reimbursement_status', $reimbursement->status)
            ->where('sent_for_updated_at', Carbon::parse($reimbursement->updated_at)->toDateTimeString())
            ->exists();
    }

    private function resolveRecipients($reimbursement)
    {
        if ((int) $reimbursement->status === 0) {
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

        if ((int) $reimbursement->status === 1) {
            return User::where('jabatan', 'Finance')
                ->whereNotNull('phoneNumber')
                ->where('phoneNumber', '!=', '')
                ->get(['name', 'phoneNumber']);
        }

        if ((int) $reimbursement->status === 2) {
            return User::where('jabatan', 'Owner')
                ->whereNotNull('phoneNumber')
                ->where('phoneNumber', '!=', '')
                ->get(['name', 'phoneNumber']);
        }

        return collect();
    }

    private function stageLabel(int $status): string
    {
        if ($status === 0) {
            return 'Head Department';
        }

        if ($status === 1) {
            return 'Finance';
        }

        return 'Owner';
    }

    private function detailUrl($reimbursement, ?string $baseUrl = null): string
    {
        $path = '/reimbursement-travel/' . $reimbursement->id;

        if ((int) $reimbursement->reimbursement_type === 1) {
            $path = '/reimbursement-driver/' . $reimbursement->id;
        }

        if ((int) $reimbursement->reimbursement_type === 3) {
            $path = '/reimbursement-entertaiment/' . $reimbursement->id;
        }

        if ($baseUrl !== null) {
            return $baseUrl . $path;
        }

        return url($path);
    }

    private function buildMessage($reimbursement, User $recipient, string $stageLabel, string $detailUrl): string
    {
        return 'Hai *' . $recipient->name . "*,\n\n" .
            'reimbursement Nomor *' . $reimbursement->no_reimbursement . '* sebesar *Rp ' . number_format($reimbursement->nominal_pengajuan, 0, ',', '.') . "* sudah menunggu 1x24 jam approval anda.\n\n" .
            'Saat ini sedang menunggu proses verifikasi oleh *' . $stageLabel . "*.\n\n" .
            "Terima kasih.\n\nKlik untuk melihat detail pengajuan : " . $detailUrl;
    }

    private function sendWhatsapp(string $target, string $message): void
    {
        $curl = \Curl::to('https://api.fonnte.com/send')
            ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
            ->withData([
                'target' => $target,
                'message' => $message,
            ])
            ->post();
    }
}