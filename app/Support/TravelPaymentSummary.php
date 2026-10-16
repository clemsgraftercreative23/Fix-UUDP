<?php

namespace App\Support;

use App\ReimbursementTravel;
use Illuminate\Support\Facades\DB;

/**
 * The Checker's Sheet columns stored on `reimbursement` for a travel claim
 * (total / per cost type, BDC and Cash) -- read back by the detail page, the
 * PDF print and Pencairan.
 *
 * Only detail rows the claim actually shows are counted: status 1 AND hanging
 * off a day of THIS reimbursement, i.e. exactly what
 * $reimbursement->travels->details renders. Summing by
 * reimbursement_travel_details.reimbursement_id alone also picked up rows no
 * screen shows (left behind by an aborted save, or whose day is gone / belongs
 * to another claim): UUDP-REIMBURSE-T-001709 printed Hotel BDC 3.510.000 while
 * its hotel rows add up to 3.120.000 (Oct 2026 report).
 */
class TravelPaymentSummary
{
    private const COST_COLUMNS = [
        8 => 'simcard',
        4 => 'flight',
        3 => 'rentalcar',
        1 => 'hotel',
        5 => 'toll',
        7 => 'gasoline',
        2 => 'taxi',
        6 => 'train',
        9 => 'others',
    ];

    public static function compute(int $reimbursementId): array
    {
        $rows = DB::table('reimbursement_travel_details as d')
            ->join('reimbursement_travel as t', 't.id', '=', 'd.reimbursement_travel_id')
            ->where('t.reimbursement_id', $reimbursementId)
            ->where('d.status', '1')
            ->groupBy('d.payment_type', 'd.cost_type_id')
            ->select(
                'd.payment_type',
                'd.cost_type_id',
                DB::raw('SUM(d.idr_rate) AS idr_total'),
                DB::raw('SUM(d.tax) AS tax_total')
            )
            ->get();

        $summary = [];
        foreach (['BDC' => 'bdc', 'Cash' => 'cash'] as $paymentType => $suffix) {
            $summary['total_' . $suffix] = 0.0;
            $summary['allowance_' . $suffix] = 0.0;
            foreach (self::COST_COLUMNS as $column) {
                $summary[$column . '_' . $suffix] = 0.0;
            }
            $summary['tax_' . $suffix] = 0.0;

            foreach ($rows as $row) {
                if ($row->payment_type !== $paymentType) {
                    continue;
                }
                $summary['total_' . $suffix] += (float) $row->idr_total;
                $summary['tax_' . $suffix] += (float) $row->tax_total;
                $column = self::COST_COLUMNS[(int) $row->cost_type_id] ?? null;
                if ($column !== null) {
                    $summary[$column . '_' . $suffix] += (float) $row->idr_total;
                }
            }
        }

        // Allowance per day is already stored in IDR and is always paid in Cash.
        $summary['allowance_cash'] = (float) ReimbursementTravel::where('reimbursement_id', $reimbursementId)->sum('allowance');

        return $summary;
    }
}
