<?php

$initialDelay = (int) env('APPROVAL_REMINDER_INITIAL_DELAY_MINUTES', 30);
$repeatInterval = (int) env('APPROVAL_REMINDER_REPEAT_INTERVAL_MINUTES', 30);
$maxDuration = (int) env('APPROVAL_REMINDER_MAX_DURATION_MINUTES', 720);

$minimumDuration = $initialDelay + $repeatInterval + 1;
if ($maxDuration < $minimumDuration) {
    $maxDuration = $minimumDuration;
}

return [
    'initial_delay_minutes' => $initialDelay,
    'repeat_interval_minutes' => $repeatInterval,
    'max_duration_minutes' => $maxDuration,
];
