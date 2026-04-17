<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReimbursementReminderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_reminder_logs')) {
            return;
        }

        Schema::create('reimbursement_reminder_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reimbursement_id');
            $table->unsignedTinyInteger('reimbursement_status');
            $table->dateTime('sent_for_updated_at');
            $table->dateTime('sent_at');
            $table->timestamps();

            $table->index(['reimbursement_id', 'reimbursement_status'], 'rrl_reminder_status_idx');
            $table->index(['reimbursement_id', 'reimbursement_status', 'sent_for_updated_at'], 'rrl_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reimbursement_reminder_logs');
    }
}