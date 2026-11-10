<?php

namespace App\Support;

/**
 * Pure rules for the superadmin "Rollback Approval" action: which earlier
 * approval step a claim may be moved back to, and which approver-name columns
 * must be blanked so the stage (and the printed signatures) are correct again.
 *
 * Status values (reimbursement.status):
 *   0  pending Head Department      1  pending HR GA
 *   2  pending Finance Supervisor   11 pending Finance Manager
 *   3  final-approved (awaiting payout)
 * Anything else (10 draft, 9 rejected, 5 paid) is not rollback-able here;
 * a paid claim goes through PencairanReimbursementController::resetSettlement.
 */
class ApprovalRollback
{
    const STATUS_PENDING_HEAD_DEPT = 0;
    const STATUS_PENDING_HR_GA = 1;
    const STATUS_PENDING_FINANCE_SUPERVISOR = 2;
    const STATUS_PENDING_FINANCE_MANAGER = 11;
    const STATUS_FINAL_APPROVED = 3;

    /** Placeholder the app stores in a mengetahui_* column that has not signed yet. */
    const UNSIGNED = '-';

    /**
     * Statuses the claim can be moved back to from $currentStatus, earliest first.
     *
     * @param int         $currentStatus
     * @param int         $type                    reimbursement.reimbursement_type (1 driver, 2 travel, 3 entertainment, 4 medical)
     * @param string|null $supervisorApprover      reimbursement.menyetujui_finance_supervisor
     * @return int[]
     */
    public static function allowedTargets($currentStatus, $type, $supervisorApprover = null)
    {
        $currentStatus = (int) $currentStatus;

        switch ($currentStatus) {
            case self::STATUS_PENDING_HR_GA:
                return [self::STATUS_PENDING_HEAD_DEPT];

            case self::STATUS_PENDING_FINANCE_SUPERVISOR:
                return [self::STATUS_PENDING_HEAD_DEPT, self::STATUS_PENDING_HR_GA];

            case self::STATUS_PENDING_FINANCE_MANAGER:
                return [
                    self::STATUS_PENDING_HEAD_DEPT,
                    self::STATUS_PENDING_HR_GA,
                    self::STATUS_PENDING_FINANCE_SUPERVISOR,
                ];

            case self::STATUS_FINAL_APPROVED:
                $targets = [
                    self::STATUS_PENDING_HEAD_DEPT,
                    self::STATUS_PENDING_HR_GA,
                    self::STATUS_PENDING_FINANCE_SUPERVISOR,
                ];
                // The Finance Manager step only exists when the Finance Supervisor
                // actually signed; an Owner approving straight from status 2 skips it.
                if (in_array((int) $type, [1, 2, 3], true) && self::hasSigned($supervisorApprover)) {
                    $targets[] = self::STATUS_PENDING_FINANCE_MANAGER;
                }
                return $targets;
        }

        return [];
    }

    public static function canRollbackTo($currentStatus, $type, $supervisorApprover, $targetStatus)
    {
        return in_array((int) $targetStatus, self::allowedTargets($currentStatus, $type, $supervisorApprover), true);
    }

    /**
     * Columns to write (in addition to status) when moving back to $targetStatus.
     * mengetahui_* use '-' (views and print templates key off it); the
     * Finance Supervisor column is nullable and goes back to NULL.
     *
     * @param int $targetStatus
     * @return array
     */
    public static function columnsToReset($targetStatus)
    {
        $targetStatus = (int) $targetStatus;
        $columns = [];

        if ($targetStatus === self::STATUS_PENDING_HEAD_DEPT) {
            $columns['mengetahui_op'] = self::UNSIGNED;
        }
        if ($targetStatus <= self::STATUS_PENDING_HR_GA) {
            $columns['mengetahui_finance'] = self::UNSIGNED;
        }
        if ($targetStatus !== self::STATUS_PENDING_FINANCE_MANAGER) {
            $columns['menyetujui_finance_supervisor'] = null;
        }
        $columns['mengetahui_owner'] = self::UNSIGNED;

        return $columns;
    }

    public static function label($status)
    {
        switch ((int) $status) {
            case self::STATUS_PENDING_HEAD_DEPT:
                return 'Head Department';
            case self::STATUS_PENDING_HR_GA:
                return 'HR GA';
            case self::STATUS_PENDING_FINANCE_SUPERVISOR:
                return 'Finance Supervisor';
            case self::STATUS_PENDING_FINANCE_MANAGER:
                return 'Finance Manager';
            case self::STATUS_FINAL_APPROVED:
                return 'Finance (Siap Pencairan)';
        }

        return 'Status ' . (int) $status;
    }

    private static function hasSigned($value)
    {
        return $value !== null && trim((string) $value) !== '' && trim((string) $value) !== self::UNSIGNED;
    }
}
