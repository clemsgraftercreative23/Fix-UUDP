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
     * Travel-only: the trip dates this user has ALREADY claimed. Unlike every
     * other type, a Travel submission holds one row per trip day in
     * reimbursement_travel, so the real trip dates live there -- the header's
     * reimbursement.date is just the first leg, which is why the generic
     * findDuplicateDates() above can't answer this for Travel.
     *
     * Deliberately scoped to ONE applicant (id_user), unlike the
     * invoice-number checks which are cross-applicant on purpose: reusing a
     * physical receipt is fraud no matter who does it, whereas two different
     * people travelling on the same date is completely normal. This answers
     * "did I already claim this very day?", not "has this evidence been used?".
     *
     * @param string[] $dates trip days (Y-m-d) about to be submitted
     * @param ?int $excludeReimbursementId see findDuplicateDates()
     * @return string[] trip dates (Y-m-d) this user already has on file
     */
    public static function findDuplicateTravelTripDates(int $userId, array $dates, ?int $excludeReimbursementId = null): array
    {
        $dates = array_values(array_unique(array_filter($dates, function ($d) {
            return is_string($d) && trim($d) !== '';
        })));

        if (empty($dates)) {
            return [];
        }

        return \App\ReimbursementTravel::query()
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
            ->where('reimbursement.id_user', $userId)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->whereIn('reimbursement_travel.date', $dates)
            ->pluck('reimbursement_travel.date')
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
     * Reverse lookup for the "reference another claim's evidence instead of
     * re-uploading" flow (Travel co-traveler rows): given a typed No.
     * Invoice/Receipt number, find who already claimed it and on which
     * reimbursement -- so a second traveler can link their own cost-line row
     * to that claim instead of uploading the same physical receipt again
     * (e.g. two travelers sharing one hotel room where only one has the
     * Guest Folio). Searches the same union of places as
     * findDuplicateInvoiceNumbers(); returns the FIRST match found.
     *
     * @return null|array{user_id:int,user_name:string,reimbursement_id:int,ticket_number:?string}
     */
    public static function findClaimOwnerByInvoiceNumber(string $number, ?int $excludeReimbursementId = null): ?array
    {
        $number = DuplicateInvoiceChecker::normalizeNumber($number);
        if ($number === '') {
            return null;
        }

        $applyExclude = function ($query) use ($excludeReimbursementId) {
            if ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            }
        };

        $match = Reimbursement::where('reimbursement.no_invoice', $number)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->when($excludeReimbursementId, $applyExclude)
            ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
            ->first();

        if (!$match) {
            $match = ReimbursementTravel::where('reimbursement_travel.no_invoice', $number)
                ->whereNull('reimbursement_travel.reference_reimbursement_id')
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->when($excludeReimbursementId, $applyExclude)
                ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
                ->first();
        }

        if (!$match) {
            $match = ReimbursementTravelDetail::where('reimbursement_travel_details.no_invoice', $number)
                ->where('reimbursement_travel_details.status', 1)
                ->whereNull('reimbursement_travel_details.reference_reimbursement_id')
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel_details.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->when($excludeReimbursementId, $applyExclude)
                ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
                ->first();
        }

        if (!$match) {
            $match = ReimbursementEntertaiment::where('reimbursement_entertaiments.no_invoice', $number)
                ->where('reimbursement_entertaiments.status', 1)
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_entertaiments.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->when($excludeReimbursementId, $applyExclude)
                ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
                ->first();
        }

        if (!$match && AppSetting::isDriverOcrCheckEnabled()) {
            $match = ReimbursementDriver::where('reimbursement_driver.no_invoice', $number)
                ->where('reimbursement_driver.status', 1)
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_driver.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->when($excludeReimbursementId, $applyExclude)
                ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
                ->first();
        }

        if (!$match) {
            return null;
        }

        $ownerReimbursement = Reimbursement::find($match->reimbursement_id);

        $tripDates = ReimbursementTravel::where('reimbursement_id', $match->reimbursement_id)
            ->selectRaw('MIN(date) as d_from, MAX(date) as d_to')
            ->first();

        return [
            'user_id' => (int) $match->id_user,
            'user_name' => (string) (\App\User::whereId($match->id_user)->value('name') ?? ''),
            'reimbursement_id' => (int) $match->reimbursement_id,
            'status' => $ownerReimbursement ? (int) $ownerReimbursement->status : null,
            'date_from' => $tripDates ? $tripDates->d_from : null,
            'date_to' => $tripDates ? $tripDates->d_to : null,
            'ticket_number' => $ownerReimbursement
                ? ($ownerReimbursement->no_reimbursement ?: $ownerReimbursement->expectedTicketNumber())
                : null,
        ];
    }

    /**
     * Same-trip (legitimate duplicate) eligibility of an owner's claim, as returned by
     * findClaimOwnerByInvoiceNumber(): (1) the owner's expense must already be approved
     * (not pending/rejected -- avoids collusion on unapproved claims), and (2) the
     * requester's trip range must overlap the owner's trip dates. Returns null when
     * eligible, otherwise a short reason code ('not_approved' | 'no_overlap').
     */
    public static function sameTripBlockReason(array $owner, ?string $from, ?string $to): ?string
    {
        if (in_array((int) ($owner['status'] ?? 0), [0, self::STATUS_REJECTED, 9], true)) {
            return 'not_approved';
        }

        $from = $from ? substr($from, 0, 10) : null;
        $to = $to ? substr($to, 0, 10) : $from;
        if (!$from || empty($owner['date_from']) || empty($owner['date_to'])) {
            return 'no_overlap';
        }
        $ownerFrom = substr((string) $owner['date_from'], 0, 10);
        $ownerTo = substr((string) $owner['date_to'], 0, 10);

        return ($from <= $ownerTo && $to >= $ownerFrom) ? null : 'no_overlap';
    }

    /**
     * Travel-only: two travelers on the same calendar date sharing one
     * Mess/Hotel invoice (e.g. one room, one bill, split between them) --
     * the second traveler's row isn't a "duplicate" in the fraud sense, it's
     * the expected shape of a shared lodging invoice. Used to auto-select
     * and lock Trip Type to Stay(MESS) on the second traveler's row instead
     * of just hard-blocking the invoice as already-used.
     *
     * Matches primarily on reimbursement_travel.no_invoice -- evidence/No.
     * Invoice for Travel is captured once per day (Step 1: Upload Evidence),
     * not per expense-line row, since the "single evidence per day" redesign
     * (Sep 2026). Falls back to the legacy reimbursement_travel_details.no_invoice
     * (a row-level invoice) only when the day-level check finds nothing --
     * keeps this working against submissions made before that redesign,
     * which still carry their invoice on the detail row instead of the day.
     *
     * @return null|array{user_id:int,user_name:string,reimbursement_id:int,date:string}
     */
    public static function findSameDayMessRelation(string $number, string $date, int $currentUserId, ?int $excludeReimbursementId = null): ?array
    {
        $number = DuplicateInvoiceChecker::normalizeNumber($number);
        if ($number === '' || $date === '') {
            return null;
        }

        $applyExclude = function ($query) use ($excludeReimbursementId) {
            if ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            }
        };

        $match = ReimbursementTravel::where('reimbursement_travel.no_invoice', $number)
            ->where('reimbursement_travel.date', $date)
            ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->where('reimbursement.id_user', '!=', $currentUserId)
            ->when($excludeReimbursementId, $applyExclude)
            ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
            ->first();

        if (!$match) {
            // Legacy fallback: pre-redesign submissions kept their invoice on the detail row.
            $match = ReimbursementTravelDetail::where('reimbursement_travel_details.no_invoice', $number)
                ->where('reimbursement_travel_details.status', 1)
                ->join('reimbursement_travel', 'reimbursement_travel.id', '=', 'reimbursement_travel_details.reimbursement_travel_id')
                ->where('reimbursement_travel.date', $date)
                ->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel_details.reimbursement_id')
                ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
                ->where('reimbursement.id_user', '!=', $currentUserId)
                ->when($excludeReimbursementId, $applyExclude)
                ->select('reimbursement.id as reimbursement_id', 'reimbursement.id_user')
                ->first();
        }

        if (!$match) {
            return null;
        }

        return [
            'user_id' => (int) $match->id_user,
            'user_name' => (string) (\App\User::whereId($match->id_user)->value('name') ?? ''),
            'reimbursement_id' => (int) $match->reimbursement_id,
            'date' => $date,
        ];
    }

    /**
     * Travel-only, self only: warn when a NEW Transaction Date falls within
     * 3 days (inclusive, either direction) of a date the SAME user already
     * has on a different Travel reimbursement -- most likely a duplicate/
     * mistaken entry of a trip already submitted, or an overlapping second
     * trip that was never meant to overlap. Deliberately excludes the
     * reimbursement currently being edited/added-to (via
     * $excludeReimbursementId): a legitimate multi-day trip has consecutive
     * Travel day-rows under the SAME header, which must never conflict with
     * each other -- only a genuinely different (other) reimbursement counts.
     *
     * @return null|array{date:string, reimbursement_id:int}
     */
    public static function findOwnTravelDateWindowConflict(int $userId, string $date, ?int $excludeReimbursementId = null): ?array
    {
        if ($date === '') {
            return null;
        }

        $target = \Carbon\Carbon::parse($date)->startOfDay();
        $windowStart = $target->copy()->subDays(2)->toDateString();
        $windowEnd = $target->copy()->addDays(2)->toDateString();

        $match = ReimbursementTravel::join('reimbursement', 'reimbursement.id', '=', 'reimbursement_travel.reimbursement_id')
            ->where('reimbursement.id_user', $userId)
            ->where('reimbursement.status', '!=', self::STATUS_REJECTED)
            ->whereBetween('reimbursement_travel.date', [$windowStart, $windowEnd])
            ->when($excludeReimbursementId, function ($query) use ($excludeReimbursementId) {
                $query->where('reimbursement.id', '!=', $excludeReimbursementId);
            })
            ->orderBy('reimbursement_travel.date')
            ->select('reimbursement_travel.date', 'reimbursement.id as reimbursement_id')
            ->first();

        if (!$match) {
            return null;
        }

        return [
            'date' => (string) $match->date,
            'reimbursement_id' => (int) $match->reimbursement_id,
        ];
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

        return 'This submission date has already been submitted before. Please submit with a different date.';
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

        return 'The date ' . $date . ' with transaction type ' . implode(', ', $duplicates)
            . ' has already been submitted before. Please submit with a different date, or make sure the transaction type differs from the existing submission (e.g. Cash vs Fleet).';
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

        return 'Invoice/receipt number ' . implode(', ', $used) . ' has already been used in a previous reimbursement submission.';
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

        return "No. Invoice/Receipt \"{$number}\" with the same date and amount has already been claimed in a previous reimbursement submission. "
            . 'Possible duplicate -- make sure this is not the same claim before continuing.';
    }
}
