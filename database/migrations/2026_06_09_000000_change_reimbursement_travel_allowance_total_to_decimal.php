<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeReimbursementTravelAllowanceTotalToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * Hindari overflow INT (2.147.483.647) pada allowance/total travel overseas.
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_travel')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reimbursement_travel MODIFY allowance DECIMAL(20,2) NOT NULL');
            DB::statement('ALTER TABLE reimbursement_travel MODIFY total DECIMAL(20,2) NOT NULL');
            return;
        }

        Schema::table('reimbursement_travel', function ($table) {
            $table->decimal('allowance', 20, 2)->change();
            $table->decimal('total', 20, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (!Schema::hasTable('reimbursement_travel')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reimbursement_travel MODIFY allowance INT NOT NULL');
            DB::statement('ALTER TABLE reimbursement_travel MODIFY total INT NOT NULL');
            return;
        }

        Schema::table('reimbursement_travel', function ($table) {
            $table->integer('allowance')->change();
            $table->integer('total')->change();
        });
    }
}
