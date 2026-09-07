<?php

namespace App\Support;

use App\ReimbursementEntertaiment;
use App\Support\ExchangeRateParser;

class EntertainmentTotal
{
    /**
     * Sum entertainment line items into parent reimbursement totals.
     * BOC is treated as BDC (legacy company-card label).
     */
    public static function computeFromRows(iterable $rows): array
    {
        $nominal = 0;
        $totalBdc = 0;
        $totalCash = 0;

        foreach ($rows as $row) {
            $paymentType = strtoupper(trim((string) ($row->payment_type ?? '')));
            $isBdc = $paymentType === 'BDC' || $paymentType === 'BOC';
            $rawAmount = ExchangeRateParser::parseFloat($row->amount ?? 0);
            $amount = $isBdc ? round($rawAmount, 2) : round($rawAmount);

            $nominal += $amount;
            if ($paymentType === 'CASH') {
                $totalCash += $amount;
            } elseif ($isBdc) {
                $totalBdc += $amount;
            }
        }

        return [
            'nominal_pengajuan' => $nominal,
            'total_bdc' => $totalBdc,
            'total_cash' => $totalCash,
        ];
    }

    public static function detailRowCount(int $reimbursementId): int
    {
        return ReimbursementEntertaiment::where('reimbursement_id', $reimbursementId)->count();
    }

    public static function computeForReimbursement(int $reimbursementId): array
    {
        $rows = ReimbursementEntertaiment::where('reimbursement_id', $reimbursementId)
            ->get(['amount', 'payment_type']);

        return self::computeFromRows($rows);
    }

    /**
     * Guard against overwriting a valid parent total when detail rows are missing
     * or cannot be summed (common on legacy imports).
     */
    public static function shouldSyncStoredTotals(int $storedNominal, array $computed, int $detailRowCount): bool
    {
        $storedNominal = (int) $storedNominal;
        $computedNominal = (int) $computed['nominal_pengajuan'];

        if ($detailRowCount === 0) {
            return false;
        }

        if ($computedNominal === 0 && $storedNominal > 0) {
            return false;
        }

        return true;
    }
}
