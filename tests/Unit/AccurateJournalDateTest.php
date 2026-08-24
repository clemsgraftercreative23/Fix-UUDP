<?php

namespace Tests\Unit;

use App\Support\AccurateJournalDate;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class AccurateJournalDateTest extends TestCase
{
    public function test_resolve_defaults_to_today_when_no_date_selected(): void
    {
        $today = Carbon::create(2026, 8, 24, 15, 30, 0);

        $this->assertTrue(AccurateJournalDate::resolve(null, $today)->isSameDay($today));
        $this->assertTrue(AccurateJournalDate::resolve('', $today)->isSameDay($today));
        $this->assertTrue(AccurateJournalDate::resolve('   ', $today)->isSameDay($today));
    }

    public function test_resolve_parses_a_valid_selected_date(): void
    {
        $today = Carbon::create(2026, 8, 24);

        $resolved = AccurateJournalDate::resolve('2026-08-20', $today);

        $this->assertSame('2026-08-20', $resolved->format('Y-m-d'));
    }

    public function test_resolve_rejects_malformed_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AccurateJournalDate::resolve('not-a-date', Carbon::create(2026, 8, 24));
    }

    public function test_resolve_rejects_calendar_rollover_dates(): void
    {
        // 30 Feb doesn't exist; Carbon would otherwise silently roll it to 2 March.
        $this->expectException(InvalidArgumentException::class);

        AccurateJournalDate::resolve('2026-02-30', Carbon::create(2026, 8, 24));
    }

    public function test_exceeds_sync_date_is_true_for_a_future_journal_date(): void
    {
        $today = Carbon::create(2026, 8, 24);
        $tomorrow = Carbon::create(2026, 8, 25);

        $this->assertTrue(AccurateJournalDate::exceedsSyncDate($tomorrow, $today));
    }

    public function test_exceeds_sync_date_is_false_for_today_or_a_past_date(): void
    {
        $today = Carbon::create(2026, 8, 24, 9, 0, 0);
        $sameDayDifferentTime = Carbon::create(2026, 8, 24, 23, 59, 0);
        $yesterday = Carbon::create(2026, 8, 23);

        $this->assertFalse(AccurateJournalDate::exceedsSyncDate($sameDayDifferentTime, $today));
        $this->assertFalse(AccurateJournalDate::exceedsSyncDate($yesterday, $today));
    }

    public function test_format_for_accurate_uses_day_month_year_slashes(): void
    {
        $date = Carbon::create(2026, 1, 5);

        $this->assertSame('05/01/2026', AccurateJournalDate::formatForAccurate($date));
    }
}
