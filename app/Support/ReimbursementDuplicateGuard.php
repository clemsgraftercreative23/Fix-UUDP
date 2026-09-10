<?php

namespace App\Support;

use App\AppSetting;
use App\Reimbursement;
use App\ReimbursementDriver;
use App\ReimbursementEntertaiment;
use App\ReimbursementTravel;
use App\ReimbursementTravelDetail;

/**
 * The DB-touching half of duplicate detection (DuplicateDateChecker /
 * DuplicateInvoiceChecker stay pure so their decision logic is unit
 * testable). Shared by the pre-submit AJAX check endpoints AND the actual
 * store()/update() methods -- the AJAX check is a convenience for the user,
 * this guard is what actually stops a duplicate from being saved.
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

    /**
     * @param ?int $excludeReimbursementId when editing an existing
     *   submission, its own row must not be flagged as a duplicate of
     *   itself when its date/invoice number is re-saved unchanged.
     * @return string[] dates (Y-m-d) already submitted (and not rejected) by this user for this reimbursement type
     */
    public static function findDuplicateDates(int $userId, int $type, array $dates, ?int $excludeReimbursementId = null): array
    {
        if (empty($dates)) {
            return [];
        }

        return Reimbursement::where('id_user', $userId)
            ->where('reimbursement_type', $type)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('id', '!=', $excludeReimbursementId);
            })
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
     * (the per-submission header, Travel's legacy per-day/item numbers, the
     * current per-row numbers on Travel/Entertainment cost lines, and
     * Driver's per-row numbers once an admin has switched Driver over to
     * OCR-derived invoices) -- a physical receipt shouldn't be claimed twice
     * regardless of which reimbursement type, form, or row it was originally
     * used on. Rejected submissions are excluded, same reasoning as
     * findDuplicateDates().
     *
     * @param ?int $excludeReimbursementId see findDuplicateDates()
     * @return string[] numbers already used, anywhere
     */
    public static function findDuplicateInvoiceNumbers(array $numbers, ?int $excludeReimbursementId = null): array
    {
        if (empty($numbers)) {
            return [];
        }

        $usedInHeader = Reimbursement::whereIn('no_invoice', $numbers)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('id', '!=', $excludeReimbursementId);
            })
            ->pluck('no_invoice')
            ->all();

        $usedInTravelItems = ReimbursementTravel::whereIn('reimbursement_travel.no_invoice', $numbers)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_travel.no_invoice')
            ->all();

        $usedInTravelDetails = ReimbursementTravelDetail::whereIn('reimbursement_travel_details.no_invoice', $numbers)
            ->where('reimbursement_travel_details.status', 1)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel_details.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_travel_details.no_invoice')
            ->all();

        $usedInEntertainmentItems = ReimbursementEntertaiment::whereIn('reimbursement_entertaiments.no_invoice', $numbers)
            ->where('reimbursement_entertaiments.status', 1)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_entertaiments.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_entertaiments.no_invoice')
            ->all();

        // Driver's invoice numbers only join the global search once an admin has
        // switched it from free-typed to OCR-derived (see AppSetting::isDriverOcrCheckEnabled())
        // -- while off, its ungoverned manual entries must never risk falsely
        // blocking a Travel/Entertainment upload.
        $usedInDriverItems = [];
        if (AppSetting::isDriverOcrCheckEnabled()) {
            $usedInDriverItems = ReimbursementDriver::whereIn('reimbursement_driver.no_invoice', $numbers)
                ->where('reimbursement_driver.status', 1)
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_driver.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                    $query->where('reimbursement.id', '!=', $excludeReimbursementId);
                })
                ->pluck('reimbursement_driver.no_invoice')
                ->all();
        }

        return array_values(array_unique(array_merge(
            $usedInHeader,
            $usedInTravelItems,
            $usedInTravelDetails,
            $usedInEntertainmentItems,
            $usedInDriverItems
        )));
    }

    /**
     * Convenience wrapper for the single-date reimbursement types
     * (driver/entertainment/medical, and travel's single-leg edit form):
     * returns a ready-to-show rejection message, or null when the date
     * isn't a duplicate (or is blank).
     */
    public static function rejectionMessageForDate(int $userId, int $type, string $date, ?int $excludeReimbursementId = null): ?string
    {
        if ($date === '') {
            return null;
        }

        if (empty(self::findDuplicateDates($userId, $type, [$date], $excludeReimbursementId))) {
            return null;
        }

        return 'Tanggal pengajuan ini sudah pernah diajukan sebelumnya. Silakan ajukan dengan tanggal yang berbeda.';
    }

    /**
     * Driver-specific: a driver can legitimately have two separate
     * settlements on the same date -- one Cash, one Fleet (company card) --
     * since those are reconciled separately. Blocking the whole date
     * outright (like findDuplicateDates() does for every other type) was
     * wrongly rejecting that case. Only the *payment type(s)* actually
     * already used on this date, by this user, count as a duplicate; a
     * different payment type on the same date is fine.
     *
     * @param string[] $paymentTypes payment types in the new submission (e.g. one per row)
     * @return string[] payment types (as submitted, case/whitespace preserved from the first match) already used on this date
     */
    public static function findDuplicateDatePaymentTypes(int $userId, string $date, array $paymentTypes, ?int $excludeReimbursementId = null): array
    {
        $requested = array_values(array_unique(array_filter(array_map(function ($p) {
            return trim((string) $p);
        }, $paymentTypes))));

        if ($date === '' || empty($requested)) {
            return [];
        }

        $existing = ReimbursementDriver::where('reimbursement_driver.status', 1)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_driver.reimbursement_id')
            ->where('reimbursement.id_user', $userId)
            ->where('reimbursement.reimbursement_type', 1)
            ->whereDate('reimbursement.date', $date)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_driver.payment_type')
            ->map(function ($p) {
                return trim((string) $p);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $requestedUpper = array_map('mb_strtoupper', $requested);
        $existingUpper = array_map('mb_strtoupper', $existing);

        $duplicates = [];
        foreach ($requested as $i => $type) {
            if (in_array($requestedUpper[$i], $existingUpper, true)) {
                $duplicates[] = $type;
            }
        }

        return array_values(array_unique($duplicates));
    }

    /** @return ?string ready-to-show rejection message, or null when no payment type on this date is a duplicate */
    public static function rejectionMessageForDatePaymentTypes(int $userId, string $date, array $paymentTypes, ?int $excludeReimbursementId = null): ?string
    {
        $duplicates = self::findDuplicateDatePaymentTypes($userId, $date, $paymentTypes, $excludeReimbursementId);
        if (empty($duplicates)) {
            return null;
        }

        return 'Tanggal ' . $date . ' dengan jenis transaksi ' . implode(', ', $duplicates)
            . ' sudah pernah diajukan sebelumnya. Silakan ajukan dengan tanggal yang berbeda, atau pastikan jenis transaksinya tidak sama dengan pengajuan yang sudah ada (mis. Cash vs Fleet).';
    }

    /** @return ?string ready-to-show rejection message, or null when none of the given numbers are duplicates */
    public static function rejectionMessageForInvoiceNumbers(array $numbers, ?int $excludeReimbursementId = null): ?string
    {
        $numbers = array_values(array_unique(array_filter($numbers, function ($n) {
            return $n !== '' && $n !== null;
        })));

        if (empty($numbers)) {
            return null;
        }

        $used = self::findDuplicateInvoiceNumbers($numbers, $excludeReimbursementId);
        if (empty($used)) {
            return null;
        }

        return 'Nomor invoice/receipt ' . implode(', ', $used) . ' sudah pernah digunakan pada pengajuan reimbursement sebelumnya.';
    }

    /**
     * The ONE duplicate rule across every reimbursement type: a claim is
     * only flagged as a duplicate when its No. Invoice/Receipt, transaction
     * date AND nominal ALL match an existing (non-rejected, not-self)
     * claim -- matching on any one or two of those alone is NOT a duplicate
     * (business decision, Sep 2026: "harus semua kondisi dulu sama baru
     * muncul duplikat"). This replaces the old, independent single-field
     * gates (date-only, invoice-only, or the date+destination+amount+type
     * combo that ignored invoice) that used to each block on their own --
     * e.g. a hotel Guest Folio (one Bill No covering "Room Charge" on 3, 4
     * and 5 Aug) would previously get its 2nd/3rd day wrongly rejected by
     * the date-only or invoice-only checks even though nothing was actually
     * being claimed twice.
     *
     * Checked against every place a claim can be recorded:
     *  - the submission header (reimbursement.no_invoice/date/nominal_pengajuan
     *    -- Medical's one-invoice-per-submission shape, and Driver/Entertainment's
     *    shared submission date);
     *  - Travel cost-line rows (no_invoice/amount on reimbursement_travel_details,
     *    date from their parent reimbursement_travel leg -- can differ per day
     *    within one trip);
     *  - Entertainment cost-line rows (no_invoice/amount on
     *    reimbursement_entertaiments, date from the submission header since
     *    every row in one Entertainment submission shares the same event date);
     *  - Driver rows (no_invoice/subtotal on reimbursement_driver, date from
     *    the submission header), only once OCR-derived Driver invoices are
     *    enabled (AppSetting::isDriverOcrCheckEnabled()) -- same gate as
     *    findDuplicateInvoiceNumbers(), since off means no_invoice there is
     *    ungoverned manual/free-typed input.
     *
     * @param mixed $amount
     */
    public static function invoiceLineAlreadyUsed(string $number, string $date, $amount, ?int $excludeReimbursementId = null): bool
    {
        $number = DuplicateInvoiceChecker::normalizeNumber($number);
        $date = trim($date);
        if (!DuplicateInvoiceChecker::isCompleteLine($number, $date, $amount)) {
            return false;
        }
        $amount = DuplicateInvoiceChecker::normalizeAmount($amount);
        $amountMatches = function ($existingAmount) use ($amount) {
            return DuplicateInvoiceChecker::amountsMatch($existingAmount, $amount);
        };

        $inHeader = Reimbursement::where('reimbursement.no_invoice', $number)
            ->whereDate('reimbursement.date', $date)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement.nominal_pengajuan')
            ->contains($amountMatches);

        if ($inHeader) {
            return true;
        }

        $inTravelDetails = ReimbursementTravelDetail::where('reimbursement_travel_details.no_invoice', $number)
            ->where('reimbursement_travel_details.status', 1)
            ->join('reimbursement_travel', 'reimbursement_travel.id', '=', 'reimbursement_travel_details.reimbursement_travel_id')
            ->whereDate('reimbursement_travel.date', $date)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel_details.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_travel_details.amount')
            ->contains($amountMatches);

        if ($inTravelDetails) {
            return true;
        }

        $inEntertainmentItems = ReimbursementEntertaiment::where('reimbursement_entertaiments.no_invoice', $number)
            ->where('reimbursement_entertaiments.status', 1)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_entertaiments.reimbursement_id')
            ->whereDate('reimbursement.date', $date)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_entertaiments.amount')
            ->contains($amountMatches);

        if ($inEntertainmentItems) {
            return true;
        }

        if (!AppSetting::isDriverOcrCheckEnabled()) {
            return false;
        }

        return ReimbursementDriver::where('reimbursement_driver.no_invoice', $number)
            ->where('reimbursement_driver.status', 1)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_driver.reimbursement_id')
            ->whereDate('reimbursement.date', $date)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->pluck('reimbursement_driver.subtotal')
            ->contains($amountMatches);
    }

    /**
     * @param mixed $amount
     * @return ?string ready-to-show rejection message, or null when this line isn't a duplicate
     */
    public static function rejectionMessageForInvoiceLine(string $number, string $date, $amount, ?int $excludeReimbursementId = null): ?string
    {
        $number = DuplicateInvoiceChecker::normalizeNumber($number);
        if ($number === '' || !self::invoiceLineAlreadyUsed($number, $date, $amount, $excludeReimbursementId)) {
            return null;
        }

        return "No. Invoice/Receipt \"{$number}\" dengan tanggal dan nominal yang sama sudah pernah diklaim pada pengajuan reimbursement sebelumnya. "
            . 'Kemungkinan duplikat -- pastikan ini bukan klaim yang sama sebelum melanjutkan.';
    }
}
