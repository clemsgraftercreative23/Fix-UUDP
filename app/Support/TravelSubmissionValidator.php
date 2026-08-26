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
}
