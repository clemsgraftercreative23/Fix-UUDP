<?php

namespace App\Jobs;

use App\Services\ApprovalReminder\ApprovalReminderService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendApprovalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    private $reminderId;

    private $logId;

    public function __construct(int $reminderId, int $logId)
    {
        $this->reminderId = $reminderId;
        $this->logId = $logId;
    }

    public function handle(ApprovalReminderService $service)
    {
        $service->deliverReminder($this->reminderId, $this->logId);
    }

    public function failed(Throwable $exception)
    {
        app(ApprovalReminderService::class)->failReminder($this->reminderId, $this->logId, $exception->getMessage());
    }
}