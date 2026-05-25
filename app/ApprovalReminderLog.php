<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApprovalReminderLog extends Model
{
    protected $table = 'approval_reminder_logs';

    protected $guarded = [];

    protected $casts = [
        'provider_response' => 'array',
        'metadata' => 'array',
    ];

    protected $dates = [
        'scheduled_for',
        'sent_at',
    ];

    public function reminder()
    {
        return $this->belongsTo('App\ApprovalReminder', 'approval_reminder_id');
    }
}