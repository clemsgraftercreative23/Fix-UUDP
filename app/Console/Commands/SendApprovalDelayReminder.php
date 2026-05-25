<?php

namespace App\Console\Commands;

use App\Services\ApprovalReminder\ApprovalReminderService;
use Illuminate\Console\Command;

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
}