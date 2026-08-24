<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class AccurateJournalDate
{
    const REQUEST_FORMAT = 'Y-m-d';

    const ACCURATE_FORMAT = 'd/m/Y';

    /**
     * Parse the Finance-selected journal date (from the "Sync ke Accurate"
     * date picker, format Y-m-d). Falls back to $today when nothing was
     * selected, so old flows without the picker keep working.
     *
     * @throws InvalidArgumentException when $input is not a real Y-m-d date.
     */
    public static function resolve(?string $input, Carbon $today): Carbon
    {
        if ($input === null || trim($input) === '') {
            return $today->copy()->startOfDay();
        }

        $parsed = Carbon::createFromFormat(self::REQUEST_FORMAT, $input);

        if ($parsed->format(self::REQUEST_FORMAT) !== $input) {
            throw new InvalidArgumentException('Format tanggal jurnal tidak valid.');
        }

        return $parsed->startOfDay();
    }

    /**
     * Finance can only backdate/date the journal up to the day Sync is
     * pressed - never into the future.
     */
    public static function exceedsSyncDate(Carbon $journalDate, Carbon $today): bool
    {
        return $journalDate->copy()->startOfDay()->gt($today->copy()->startOfDay());
    }

    public static function formatForAccurate(Carbon $journalDate): string
    {
        return $journalDate->format(self::ACCURATE_FORMAT);
    }
}
