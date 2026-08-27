<?php

namespace Tests\Unit;

use App\Support\DuplicateDateChecker;
use Tests\TestCase;

class DuplicateDateCheckerTest extends TestCase
{
    public function test_normalize_dates_uses_dates_array_when_present(): void
    {
        $dates = DuplicateDateChecker::normalizeDates(['2026-08-20', '2026-08-21', '2026-08-20'], '2026-08-25');

        $this->assertSame(['2026-08-20', '2026-08-21'], $dates);
    }

    public function test_normalize_dates_falls_back_to_single_date_when_no_array_given(): void
    {
        $dates = DuplicateDateChecker::normalizeDates(null, '2026-08-25');

        $this->assertSame(['2026-08-25'], $dates);
    }

    public function test_normalize_dates_filters_out_empty_values(): void
    {
        $dates = DuplicateDateChecker::normalizeDates(['2026-08-20', '', null], null);

        $this->assertSame(['2026-08-20'], $dates);
    }

    public function test_normalize_dates_filters_out_non_string_values(): void
    {
        $dates = DuplicateDateChecker::normalizeDates(['2026-08-20', 12345, true, ['nested']], null);

        $this->assertSame(['2026-08-20'], $dates);
    }

    public function test_normalize_dates_returns_empty_array_when_nothing_usable_is_given(): void
    {
        $this->assertSame([], DuplicateDateChecker::normalizeDates(null, null));
        $this->assertSame([], DuplicateDateChecker::normalizeDates([], ''));
    }

    public function test_build_response_flags_duplicate_when_dates_overlap(): void
    {
        $response = DuplicateDateChecker::buildResponse(['2026-08-20', '2026-08-21'], ['2026-08-20']);

        $this->assertTrue($response['duplicate']);
        $this->assertSame('DUPLICATE_REIMBURSEMENT_DATE', $response['code']);
        $this->assertSame(['2026-08-20'], $response['duplicate_dates']);
        $this->assertStringContainsString('2026-08-20', $response['message']);
    }

    public function test_build_response_reports_ok_when_no_overlap(): void
    {
        $response = DuplicateDateChecker::buildResponse(['2026-08-22'], ['2026-08-20']);

        $this->assertFalse($response['duplicate']);
        $this->assertSame('OK', $response['code']);
        $this->assertSame([], $response['duplicate_dates']);
    }

    public function test_build_response_ignores_existing_dates_outside_requested_range(): void
    {
        $response = DuplicateDateChecker::buildResponse(['2026-08-22', '2026-08-23'], ['2026-08-20', '2026-08-23']);

        $this->assertTrue($response['duplicate']);
        $this->assertSame(['2026-08-23'], $response['duplicate_dates']);
    }
}
