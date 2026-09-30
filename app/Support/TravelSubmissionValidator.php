<?php

namespace App\Support;

/**
 * Pure validation for the main travel-reimbursement submission form
 * (TravelReimbursementController@store). A final submit must have every
 * detail row (any row where a cost type was picked) fully filled in --
 * including Payment Type -- before it's allowed to save. Bug history: this
 * was previously only enforced client-side (HTML5 `required`), which a
 * production submission (id 1544) slipped past with an empty Payment Type.
 */
class TravelSubmissionValidator
{
    private const FIELD_LABELS = [
        'destination' => 'Remarks / tujuan biaya',
        'currency' => 'Mata uang',
        'payment_type' => 'Tipe pembayaran',
        'amount' => 'Jumlah',
    ];

    /**
     * @param array $legs the "reimburse" array from the request (one entry per travel day)
     * @return string[] human-readable error messages; empty when the submission is complete
     */
    public static function findErrors(array $legs): array
    {
        $errors = [];

        if (empty($legs)) {
            $errors[] = 'Minimal satu hari perjalanan harus diisi.';
            return $errors;
        }

        foreach ($legs as $legIndex => $leg) {
            $legNumber = $legIndex + 1;
            if (self::isExpenseFreeLeg((array) $leg, $legIndex)) {
                continue;
            }
            $details = (array) ($leg['detail'] ?? []);
            $hasDetail = false;

            foreach ($details as $detailIndex => $detail) {
                if (trim((string) ($detail['cost_type_id'] ?? '')) === '') {
                    continue;
                }

                $hasDetail = true;
                foreach (self::FIELD_LABELS as $field => $label) {
                    if (trim((string) ($detail[$field] ?? '')) === '') {
                        $errors[] = "{$label} pada hari ke-{$legNumber}, baris rincian ke-" . ($detailIndex + 1) . ' wajib diisi.';
                    }
                }
            }

            if (!$hasDetail) {
                $errors[] = "Minimal satu rincian biaya (cost type) pada hari ke-{$legNumber} harus diisi lengkap.";
            }
        }

        return $errors;
    }

    /**
     * Days that legitimately claim no expense -- Travel Allowance only via a
     * refer to an earlier day, the single-day allowance-only option, or a
     * same-trip co-traveler reference -- are exempt from the "at least one
     * complete detail row" requirement. An invalid refer (non-numeric or not
     * pointing at an earlier day) is NOT exempt and falls through to the
     * normal requirement. A same-trip reference_invoice is only exempted
     * from THIS check; whether that invoice actually exists is still
     * verified downstream by resolveReferencedRowInvoice().
     */
    private static function isExpenseFreeLeg(array $leg, $legIndex): bool
    {
        if (!empty($leg['allowance_only'])) {
            return true;
        }

        $referDay = $leg['refer_day'] ?? null;
        if ($referDay !== null && $referDay !== '' && is_numeric($referDay) && (int) $referDay < (int) $legIndex) {
            return true;
        }

        if (trim((string) ($leg['reference_invoice'] ?? '')) !== '') {
            return true;
        }

        return false;
    }
}
