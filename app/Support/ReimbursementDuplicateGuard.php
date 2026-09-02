<?php

namespace App\Support;

use App\Reimbursement;
use App\ReimbursementTravel;

/**
 * The DB-touching half of duplicate detection (DuplicateDateChecker /
 * DuplicateInvoiceChecker stay pure so their decision logic is unit
 * testable). Shared by the pre-submit AJAX check endpoints AND the actual
 * store() methods -- the AJAX check is a convenience for the user, this
 * guard is what actually stops a duplicate from being saved.
 */
class ReimbursementDuplicateGuard
{
    /**
     * reimbursement.status value for a rejected submission (see e.g.
     * DriverReimbursementController's status badge mapping). A rejected
     * submission doesn't count as "already used" -- resubmitting the same
     * date/invoice number after a rejection is the normal, expected flow.
     */
    private const STATUS_REJECTED = 4;

    /** @return string[] dates (Y-m-d) already submitted (and not rejected) by this user for this reimbursement type */
    public static function findDuplicateDates(int $userId, int $type, array $dates): array
    {
        if (empty($dates)) {
            return [];
        }

        return Reimbursement::where('id_user', $userId)
            ->where('reimbursement_type', $type)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->whereIn('date', $dates)
            ->pluck('date')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Checked across every place an invoice/receipt number can be saved
     * (the per-submission header, and Travel's per-day/item numbers) -- a
     * physical receipt shouldn't be claimed twice regardless of which
     * reimbursement type or form it was originally used on. Rejected
     * submissions are excluded, same reasoning as findDuplicateDates().
     *
     * @return string[] numbers already used, anywhere
     */
    public static function findDuplicateInvoiceNumbers(array $numbers): array
    {
        if (empty($numbers)) {
            return [];
        }

        $usedInHeader = Reimbursement::whereIn('no_invoice', $numbers)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->pluck('no_invoice')
            ->all();

        $usedInTravelItems = ReimbursementTravel::whereIn('reimbursement_travel.no_invoice', $numbers)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->pluck('reimbursement_travel.no_invoice')
            ->all();

        return array_values(array_unique(array_merge($usedInHeader, $usedInTravelItems)));
    }

    /**
     * Convenience wrapper for the single-date reimbursement types
     * (driver/entertainment/medical): returns a ready-to-show rejection
     * message, or null when the date isn't a duplicate (or is blank).
     */
    public static function rejectionMessageForDate(int $userId, int $type, string $date): ?string
    {
        if ($date === '') {
            return null;
        }

        if (empty(self::findDuplicateDates($userId, $type, [$date]))) {
            return null;
        }

        return 'Tanggal pengajuan ini sudah pernah diajukan sebelumnya. Silakan ajukan dengan tanggal yang berbeda.';
    }

    /** @return ?string ready-to-show rejection message, or null when none of the given numbers are duplicates */
    public static function rejectionMessageForInvoiceNumbers(array $numbers): ?string
    {
        $numbers = array_values(array_unique(array_filter($numbers, function ($n) {
            return $n !== '' && $n !== null;
        })));

        if (empty($numbers)) {
            return null;
        }

        $used = self::findDuplicateInvoiceNumbers($numbers);
        if (empty($used)) {
            return null;
        }

        return 'Nomor invoice/receipt ' . implode(', ', $used) . ' sudah pernah digunakan pada pengajuan reimbursement sebelumnya.';
    }
}
