<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRemindersTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('approval_reminders')) {
            Schema::create('approval_reminders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->string('workflow_code', 100);
                $table->unsignedSmallInteger('source_status');
                $table->string('stage_code', 50);
                $table->string('stage_label');
                $table->string('recipient_name')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->dateTime('first_due_at');
                $table->dateTime('next_send_at');
                $table->dateTime('expires_at');
                $table->dateTime('last_sent_at')->nullable();
                $table->dateTime('last_attempt_at')->nullable();
                $table->unsignedSmallInteger('send_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->dateTime('stopped_at')->nullable();
                $table->string('stopped_reason', 100)->nullable();
                $table->text('last_error')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['subject_type', 'subject_id', 'workflow_code'], 'approval_reminders_unique_subject');
                $table->index(['is_active', 'next_send_at'], 'approval_reminders_due_idx');
                $table->index(['is_active', 'expires_at'], 'approval_reminders_expiry_idx');
            });
        }

        if (!Schema::hasTable('approval_reminder_logs')) {
            Schema::create('approval_reminder_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('approval_reminder_id');
                $table->dateTime('scheduled_for');
                $table->dateTime('sent_at')->nullable();
                $table->string('status', 30)->default('queued');
                $table->unsignedSmallInteger('recipient_count')->default(0);
                $table->json('provider_response')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['approval_reminder_id', 'scheduled_for'], 'approval_reminder_logs_unique_schedule');
                $table->index(['status', 'scheduled_for'], 'approval_reminder_logs_status_idx');

                $table->foreign('approval_reminder_id')
                    ->references('id')
                    ->on('approval_reminders')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('approval_reminder_logs');
        Schema::dropIfExists('approval_reminders');
    }
}