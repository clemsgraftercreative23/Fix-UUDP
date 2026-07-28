<?php

namespace Tests\Unit;

use App\Support\EntertainmentTotal;
use Tests\TestCase;

class EntertainmentTotalTest extends TestCase
{
    public function test_compute_from_rows_sums_bdc_boc_and_cash(): void
    {
        $rows = [
            (object) ['payment_type' => 'BOC', 'amount' => 280000],
            (object) ['payment_type' => 'Cash', 'amount' => 50000],
            (object) ['payment_type' => 'BDC', 'amount' => 100000],
        ];

        $totals = EntertainmentTotal::computeFromRows($rows);

        $this->assertSame(370000, $totals['nominal_pengajuan']);
        $this->assertSame(380000, $totals['total_bdc']);
        $this->assertSame(50000, $totals['total_cash']);
    }

    public function test_should_not_sync_when_detail_rows_missing(): void
    {
        $this->assertFalse(EntertainmentTotal::shouldSyncStoredTotals(440055, [
            'nominal_pengajuan' => 0,
            'total_bdc' => 0,
            'total_cash' => 0,
        ], 0));
    }

    public function test_should_not_zero_existing_nominal_when_computed_is_zero(): void
    {
        $this->assertFalse(EntertainmentTotal::shouldSyncStoredTotals(440055, [
            'nominal_pengajuan' => 0,
            'total_bdc' => 0,
            'total_cash' => 0,
        ], 2));
    }

    public function test_should_sync_when_stored_zero_and_computed_positive(): void
    {
        $this->assertTrue(EntertainmentTotal::shouldSyncStoredTotals(0, [
            'nominal_pengajuan' => 280000,
            'total_bdc' => 280000,
            'total_cash' => 0,
        ], 1));
    }
}
