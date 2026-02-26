<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnRejectReasonOnTableReimbursement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reimbursement', function (Blueprint $table) {
            //
            $table->string('reject_reason')->nullable();
            $table->string('reject_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reimbursement', function (Blueprint $table) {
            //
            $table->dropColumn(['reject_reason','reject_by']);
        });
    }
}
