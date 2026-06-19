<?php

namespace App\Support;

/**
 * Total per hari travel = allowance IDR + jumlah detail (idr_rate).
 */
class TravelDayTotal
{
    /**
     * @param  object|array  $travelRow  reimbursement_travel row (needs allowance)
     * @param  iterable|null  $details  reimbursement_travel_details rows
     */
    public static function compute($travelRow, ?iterable $details = null): float
    {
        $allowance = is_array($travelRow)
            ? (float) ($travelRow['allowance'] ?? 0)
            : (float) ($travelRow->allowance ?? 0);

        $total = $allowance;

        if ($details === null && is_object($travelRow) && isset($travelRow->details)) {
            $details = $travelRow->details;
        }

        if ($details === null) {
            return $total;
        }

        foreach ($details as $detail) {
            $idr = is_array($detail)
                ? (float) ($detail['idr_rate'] ?? 0)
                : (float) ($detail->idr_rate ?? 0);
            $total += $idr;
        }

        return $total;
    }
}
