<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApprovalReminder extends Model
{
    protected $table = 'approval_reminders';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'send_count' => 'integer',
        'source_status' => 'integer',
    ];

    protected $dates = [
        'first_due_at',
        'next_send_at',
        'expires_at',
        'last_sent_at',
        'last_attempt_at',
        'stopped_at',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function logs()
    {
        return $this->hasMany('App\ApprovalReminderLog', 'approval_reminder_id');
    }
}