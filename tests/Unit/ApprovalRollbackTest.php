<?php

namespace Tests\Unit;

use App\Support\ApprovalRollback;
use App\Support\JabatanClassifier;
use Tests\TestCase;

class ApprovalRollbackTest extends TestCase
{
    public function test_status_without_a_previous_step_has_no_targets(): void
    {
        foreach ([10, 0, 9, 5, 4, 99] as $status) {
            $this->assertSame([], ApprovalRollback::allowedTargets($status, 2, 'Supervisor A'), "status $status");
        }
    }

    public function test_pending_hr_ga_can_only_go_back_to_head_dept(): void
    {
        $this->assertSame([0], ApprovalRollback::allowedTargets(1, 2, null));
    }

    public function test_pending_finance_supervisor_can_go_back_to_head_dept_or_hr_ga(): void
    {
        $this->assertSame([0, 1], ApprovalRollback::allowedTargets(2, 3, null));
    }

    public function test_pending_finance_manager_can_go_back_to_any_earlier_step(): void
    {
        $this->assertSame([0, 1, 2], ApprovalRollback::allowedTargets(11, 1, 'Supervisor A'));
    }

    public function test_final_approved_offers_finance_manager_step_only_when_supervisor_signed(): void
    {
        $this->assertSame([0, 1, 2, 11], ApprovalRollback::allowedTargets(3, 2, 'Supervisor A'));
    }

    public function test_final_approved_via_owner_shortcut_skips_finance_manager_step(): void
    {
        $this->assertSame([0, 1, 2], ApprovalRollback::allowedTargets(3, 2, null));
        $this->assertSame([0, 1, 2], ApprovalRollback::allowedTargets(3, 2, ''));
        $this->assertSame([0, 1, 2], ApprovalRollback::allowedTargets(3, 2, '-'));
    }

    public function test_medical_never_gets_a_finance_manager_target(): void
    {
        $this->assertSame([0, 1, 2], ApprovalRollback::allowedTargets(3, 4, 'Supervisor A'));
        $this->assertSame([0, 1], ApprovalRollback::allowedTargets(2, 4, null));
    }

    public function test_can_rollback_to_checks_membership(): void
    {
        $this->assertTrue(ApprovalRollback::canRollbackTo(3, 2, 'Supervisor A', 11));
        $this->assertFalse(ApprovalRollback::canRollbackTo(3, 2, null, 11));
        $this->assertFalse(ApprovalRollback::canRollbackTo(2, 2, null, 2));
        $this->assertFalse(ApprovalRollback::canRollbackTo(1, 2, null, '3'));
        $this->assertTrue(ApprovalRollback::canRollbackTo(2, 2, null, '1'));
    }

    public function test_reset_to_head_dept_clears_every_approver_column(): void
    {
        $this->assertSame([
            'mengetahui_op' => '-',
            'mengetahui_finance' => '-',
            'menyetujui_finance_supervisor' => null,
            'mengetahui_owner' => '-',
        ], ApprovalRollback::columnsToReset(0));
    }

    public function test_reset_to_hr_ga_keeps_head_dept_signature(): void
    {
        $columns = ApprovalRollback::columnsToReset(1);

        $this->assertArrayNotHasKey('mengetahui_op', $columns);
        $this->assertSame('-', $columns['mengetahui_finance']);
        $this->assertNull($columns['menyetujui_finance_supervisor']);
        $this->assertSame('-', $columns['mengetahui_owner']);
    }

    public function test_reset_to_finance_supervisor_keeps_head_dept_and_hr_ga(): void
    {
        $this->assertSame([
            'menyetujui_finance_supervisor' => null,
            'mengetahui_owner' => '-',
        ], ApprovalRollback::columnsToReset(2));
    }

    public function test_reset_to_finance_manager_only_clears_the_owner_signature(): void
    {
        $this->assertSame(['mengetahui_owner' => '-'], ApprovalRollback::columnsToReset(11));
    }

    public function test_unsigned_mengetahui_columns_use_dash_never_null(): void
    {
        foreach ([0, 1, 2, 11] as $target) {
            foreach (ApprovalRollback::columnsToReset($target) as $column => $value) {
                if (strpos($column, 'mengetahui_') === 0) {
                    $this->assertSame('-', $value, "$column for target $target");
                }
            }
        }
    }

    public function test_labels(): void
    {
        $this->assertSame('Head Department', ApprovalRollback::label(0));
        $this->assertSame('HR GA', ApprovalRollback::label(1));
        $this->assertSame('Finance Supervisor', ApprovalRollback::label(2));
        $this->assertSame('Finance Manager', ApprovalRollback::label(11));
    }

    public function test_only_superadmin_and_admin_can_rollback_approval(): void
    {
        $this->assertTrue(JabatanClassifier::canRollbackApproval('superadmin'));
        $this->assertTrue(JabatanClassifier::canRollbackApproval('admin'));
        $this->assertFalse(JabatanClassifier::canRollbackApproval('Owner'));
        $this->assertFalse(JabatanClassifier::canRollbackApproval('Finance'));
        $this->assertFalse(JabatanClassifier::canRollbackApproval('Finance Manager'));
        $this->assertFalse(JabatanClassifier::canRollbackApproval('karyawan'));
        $this->assertFalse(JabatanClassifier::canRollbackApproval(null));
    }
}
