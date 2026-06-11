<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixTravelFinanceSupervisorStatus extends Migration
{
    /**
     * Repair travel claims where Finance Supervisor was recorded but status stayed at HR GA (2).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement') || !Schema::hasColumn('reimbursement', 'menyetujui_finance_supervisor')) {
            return;
        }

        DB::table('reimbursement')
            ->where('reimbursement_type', 2)
            ->where('status', 2)
            ->whereNotNull('menyetujui_finance_supervisor')
            ->where('menyetujui_finance_supervisor', '!=', '')
            ->where('menyetujui_finance_supervisor', '!=', '-')
            ->update(['status' => 11]);
    }

    /**
     * @return void
     */
    public function down()
    {
        // Data repair is not safely reversible.
    }
}
