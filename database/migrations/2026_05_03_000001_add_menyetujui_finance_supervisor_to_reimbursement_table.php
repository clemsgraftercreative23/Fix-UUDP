<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenyetujuiFinanceSupervisorToReimbursementTable extends Migration
{
    /**
     * Run the migrations.
     * Status 11 = approved Finance Supervisor, awaiting Finance Manager (driver & entertainment).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }
        Schema::table('reimbursement', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement', 'menyetujui_finance_supervisor')) {
                $table->string('menyetujui_finance_supervisor', 191)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }
        Schema::table('reimbursement', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement', 'menyetujui_finance_supervisor')) {
                $table->dropColumn('menyetujui_finance_supervisor');
            }
        });
    }
}
