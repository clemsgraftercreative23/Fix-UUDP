<?php

namespace App\Console\Commands;

use App\Services\ApprovalReminder\ApprovalReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendApprovalDelayReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursement:send-approval-delay-reminder {--dry-run : Show which reminders would be queued without sending WhatsApp messages} {--base-url= : Override app base URL for detail links, e.g. https://uudp.sf-indonesia.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync and queue hourly approval reminders for pending reimbursements';

    private $service;

    public function __construct(ApprovalReminderService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->logReminderConfig();

        $dryRun = (bool) $this->option('dry-run');
        $baseUrlOption = $this->option('base-url');
        $baseUrl = is_string($baseUrlOption) && trim($baseUrlOption) !== ''
            ? rtrim(trim($baseUrlOption), '/')
            : null;

        if ($dryRun) {
            $result = $this->service->previewDueReminders($baseUrl);

            foreach ($result as $item) {
                $this->line('[DRY RUN] ' . $item['no_reimbursement'] . ' | ' . $item['stage_label'] . ' | ' . $item['recipients'] . ' | ' . $item['next_send_at']);
            }

            $this->info('Dry run complete: ' . count($result) . ' reminder(s) eligible');

            return 0;
        }

        $result = $this->service->processReimbursementReminders(false, $baseUrl);

        $this->info('Reminder sync complete. Queued: ' . (int) ($result['queued'] ?? 0) . ', skipped: ' . (int) ($result['skipped'] ?? 0));

        return 0;
    }

    private function logReminderConfig(): void
    {
        $initial = (int) config('approval_reminder.initial_delay_minutes');
        $repeat = (int) config('approval_reminder.repeat_interval_minutes');
        $max = (int) config('approval_reminder.max_duration_minutes');
        $queue = (string) config('queue.default');

        if ($max <= $initial + $repeat) {
            Log::warning('Approval reminder max_duration is too short for repeat cadence', [
                'initial_delay_minutes' => $initial,
                'repeat_interval_minutes' => $repeat,
                'max_duration_minutes' => $max,
            ]);
        }

        Log::debug('Approval reminder run', [
            'initial_delay_minutes' => $initial,
            'repeat_interval_minutes' => $repeat,
            'max_duration_minutes' => $max,
            'queue_connection' => $queue,
        ]);
    }
}