<?php

namespace Tests\Unit;

use App\Support\TravelDayTotal;
use Tests\TestCase;

class TravelDayTotalTest extends TestCase
{
    public function test_computes_allowance_plus_detail_idr_rates(): void
    {
        $travel = (object) ['allowance' => 965682.0];
        $details = [
            (object) ['idr_rate' => 183472.0],
        ];

        $this->assertSame(1149154.0, TravelDayTotal::compute($travel, $details));
    }

    public function test_computes_total_with_bdc_decimal_idr_rate(): void
    {
        $travel = (object) ['allowance' => 965682.0];
        $details = [
            (object) ['idr_rate' => 183472.66],
        ];

        $this->assertSame(1149154.66, TravelDayTotal::compute($travel, $details));
    }

    public function test_allowance_only_day_matches_print_total(): void
    {
        $travel = (object) ['allowance' => 965682.0];
        $details = [];

        $this->assertSame(965682.0, TravelDayTotal::compute($travel, $details));
    }
}
