<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendApprovalDelayReminder extends Command
{
    /** First reminder once a claim stays this long at the same approval stage (`updated_at`). */
    private const INITIAL_DELAY_HOURS = 2;

    /** Subsequent reminders while the stage is unchanged (see `shouldSkipReminder`). */
    private const REPEAT_INTERVAL_HOURS = 1;

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
    protected $description = 'Send WA reminders: first after 2h at current approval stage, then every 1h until status advances';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $threshold = Carbon::now()->subHours(self::INITIAL_DELAY_HOURS);
        $dryRun = (bool) $this->option('dry-run');
        $baseUrlOption = $this->option('base-url');
        $baseUrl = is_string($baseUrlOption) && trim($baseUrlOption) !== ''
            ? rtrim(trim($baseUrlOption), '/')
            : null;

        // Jangan hanya filter `updated_at <= threshold`: jika baris disentuh (updated_at maju)
        // tanpa berubah status, klaim tetap harus masuk agar pengingat per jam tetap jalan.
        $reimbursements = Reimbursement::query()
            ->whereIn('reimbursement_type', [1, 2, 3])
            ->whereIn('status', [0, 1, 2])
            ->where(function ($q) use ($threshold) {
                $q->where('updated_at', '<=', $threshold)
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('reimbursement_reminder_logs as rrl')
                            ->whereColumn('rrl.reimbursement_id', 'reimbursement.id')
                            ->whereColumn('rrl.reimbursement_status', 'reimbursement.status');
                    });
            })
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
            if ($this->shouldSkipReminder($reimbursement)) {
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

    /**
     * Skip if first reminder is too soon, or if the last reminder for this status was too recent.
     * Uses the latest log per (reimbursement_id, status) so hourly repeats keep working even when
     * `updated_at` is bumped without a real status change (previously that dropped rows from the query
     * and broke the 1h cadence).
     */
    private function shouldSkipReminder($reimbursement): bool
    {
        $statusUpdatedAt = Carbon::parse($reimbursement->updated_at);
        $now = Carbon::now();

        $lastLog = DB::table('reimbursement_reminder_logs')
            ->where('reimbursement_id', $reimbursement->id)
            ->where('reimbursement_status', $reimbursement->status)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->first(['sent_at']);

        if ($lastLog === null) {
            return $statusUpdatedAt->gt($now->copy()->subHours(self::INITIAL_DELAY_HOURS));
        }

        return Carbon::parse($lastLog->sent_at)->gt($now->copy()->subHours(self::REPEAT_INTERVAL_HOURS));
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
            'reimbursement Nomor *' . $reimbursement->no_reimbursement . '* sebesar *Rp ' . number_format($reimbursement->nominal_pengajuan, 0, ',', '.') . '* sudah menunggu lebih dari *' . self::INITIAL_DELAY_HOURS . "* jam* pada tahap approval saat ini (pengingat berkala setiap " . self::REPEAT_INTERVAL_HOURS . " jam).\n\n" .
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