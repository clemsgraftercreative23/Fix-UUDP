<?php

namespace Tests\Unit;

use App\Repositories\ApprovalReminderRepository;
use Tests\TestCase;

class ApprovalReminderRecipientTest extends TestCase
{
    public function test_entertainment_hr_ga_stage_does_not_include_finance_supervisor()
    {
        $repository = new ApprovalReminderRepository();

        $this->assertSame('HR GA', $repository->stageLabel(1));
        $this->assertSame(
            ['Finance', 'HR GA'],
            $repository->approverJabatanForStage(3, 1)
        );
    }

    public function test_travel_hr_ga_stage_does_not_include_finance_supervisor()
    {
        $repository = new ApprovalReminderRepository();

        $this->assertSame(
            ['Finance', 'HR', 'HR GA'],
            $repository->approverJabatanForStage(2, 1)
        );
    }

    public function test_finance_supervisor_stage_targets_supervisor_and_owner()
    {
        $repository = new ApprovalReminderRepository();

        $this->assertSame(
            ['Finance Supervisor', 'Owner'],
            $repository->approverJabatanForStage(3, 2)
        );
    }
}
