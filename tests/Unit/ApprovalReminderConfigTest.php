<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApprovalReminderConfigTest extends TestCase
{
    public function test_max_duration_is_at_least_initial_plus_repeat_plus_one()
    {
        $initial = (int) config('approval_reminder.initial_delay_minutes');
        $repeat = (int) config('approval_reminder.repeat_interval_minutes');
        $max = (int) config('approval_reminder.max_duration_minutes');

        $this->assertGreaterThan(
            $initial + $repeat,
            $max,
            'max_duration must allow at least one repeat after the first reminder'
        );
    }
}
