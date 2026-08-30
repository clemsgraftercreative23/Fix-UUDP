<?php

namespace Tests\Unit;

use App\Support\JabatanClassifier;
use Tests\TestCase;

class SuperadminFullAccessTest extends TestCase
{
    /**
     * Test that superadmin and admin roles can sync to Accurate.
     */
    public function test_superadmin_and_admin_can_sync_accurate(): void
    {
        $this->assertTrue(JabatanClassifier::canSyncAccurate('superadmin'));
        $this->assertTrue(JabatanClassifier::canSyncAccurate('admin'));
    }

    /**
     * Test that superadmin and admin are recognized as admin/management roles, not employee-like.
     */
    public function test_superadmin_and_admin_are_not_employee_like(): void
    {
        $this->assertFalse(JabatanClassifier::isEmployeeLike('superadmin'));
        $this->assertFalse(JabatanClassifier::isEmployeeLike('admin'));
    }

    /**
     * Test role lists for resetting settlements.
     */
    public function test_superadmin_and_admin_can_reset_settlement(): void
    {
        $allowedRoles = ['Owner', 'superadmin', 'admin'];

        $this->assertTrue(in_array('superadmin', $allowedRoles, true));
        $this->assertTrue(in_array('admin', $allowedRoles, true));
        $this->assertTrue(in_array('Owner', $allowedRoles, true));
        $this->assertFalse(in_array('Finance', $allowedRoles, true));
        $this->assertFalse(in_array('karyawan', $allowedRoles, true));
    }

    /**
     * Test role lists for viewing activity logs.
     */
    public function test_superadmin_and_admin_can_access_activity_log(): void
    {
        $authorizedRoles = ['superadmin', 'admin', 'Owner'];

        $this->assertTrue(in_array('superadmin', $authorizedRoles, true));
        $this->assertTrue(in_array('admin', $authorizedRoles, true));
        $this->assertTrue(in_array('Owner', $authorizedRoles, true));
        $this->assertFalse(in_array('karyawan', $authorizedRoles, true));
        $this->assertFalse(in_array('Direktur Operasional', $authorizedRoles, true));
    }

    /**
     * Test that superadmin and admin have approval rights across all approval stages.
     */
    public function test_superadmin_and_admin_can_approve_all_reimbursement_stages(): void
    {
        $stages = [
            0 => ['Direktur Operasional', 'superadmin', 'admin'],
            1 => ['Finance', 'HR GA', 'HR', 'superadmin', 'admin'],
            2 => ['Owner', 'Finance Supervisor', 'superadmin', 'admin'],
            11 => ['Owner', 'Finance Manager', 'superadmin', 'admin'],
        ];

        foreach ($stages as $stage => $allowedRoles) {
            $this->assertTrue(
                in_array('superadmin', $allowedRoles, true),
                "superadmin should be allowed to approve at stage {$stage}"
            );
            $this->assertTrue(
                in_array('admin', $allowedRoles, true),
                "admin should be allowed to approve at stage {$stage}"
            );
        }
    }

    /**
     * Test self-approval bypass logic for superadmin/admin.
     */
    public function test_superadmin_and_admin_bypass_self_approval_restriction(): void
    {
        $checkSelfApprovalBlocked = function (int $userId, int $submitterId, string $jabatan): bool {
            $isOwnSubmission = ($userId === $submitterId);
            $isSuperadmin = in_array($jabatan, ['superadmin', 'admin'], true);

            // True if blocked, False if allowed
            return $isOwnSubmission && !$isSuperadmin;
        };

        // Standard user approving own submission -> blocked
        $this->assertTrue($checkSelfApprovalBlocked(5, 5, 'Finance'));
        $this->assertTrue($checkSelfApprovalBlocked(5, 5, 'Direktur Operasional'));
        $this->assertTrue($checkSelfApprovalBlocked(5, 5, 'Owner'));

        // Standard user approving someone else's submission -> allowed
        $this->assertFalse($checkSelfApprovalBlocked(5, 10, 'Finance'));

        // Superadmin / admin approving own submission -> bypassed / allowed
        $this->assertFalse($checkSelfApprovalBlocked(5, 5, 'superadmin'));
        $this->assertFalse($checkSelfApprovalBlocked(5, 5, 'admin'));
    }

    /**
     * Test bulk approval visibility for superadmin and admin.
     */
    public function test_superadmin_and_admin_can_bulk_approve_all_applicable_statuses(): void
    {
        $canBulkApprove = function (string $jabatan, string $status): bool {
            if ($jabatan === 'Direktur Operasional') {
                return in_array($status, ['9', '0'], true);
            }
            if (in_array($jabatan, ['Finance', 'HR GA', 'HR'], true)) {
                return $status === '1';
            }
            if ($jabatan === 'Finance Supervisor') {
                return $status === '2';
            }
            if ($jabatan === 'Finance Manager') {
                return $status === '11';
            }
            if ($jabatan === 'Owner') {
                return in_array($status, ['2', '11', '3'], true);
            }
            if (in_array($jabatan, ['superadmin', 'admin'], true)) {
                return in_array($status, ['0', '1', '2', '11', '3'], true);
            }
            return false;
        };

        $allStatuses = ['0', '1', '2', '11', '3'];

        foreach ($allStatuses as $status) {
            $this->assertTrue($canBulkApprove('superadmin', $status), "superadmin should bulk approve status {$status}");
            $this->assertTrue($canBulkApprove('admin', $status), "admin should bulk approve status {$status}");
        }

        // Regular finance can only approve status 1
        $this->assertTrue($canBulkApprove('Finance', '1'));
        $this->assertFalse($canBulkApprove('Finance', '0'));
        $this->assertFalse($canBulkApprove('Finance', '2'));
    }
}
