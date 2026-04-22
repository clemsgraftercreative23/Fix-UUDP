<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeTravelDetailRatesToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * Menjaga nilai desimal pada hasil kalkulasi Exchange Rate x Amount.
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_travel_details')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY idr_rate DECIMAL(20,2) NOT NULL");
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY tax DECIMAL(20,2) NULL");
            return;
        }

        // Fallback untuk driver lain yang mendukung schema change.
        Schema::table('reimbursement_travel_details', function ($table) {
            $table->decimal('idr_rate', 20, 2)->change();
            $table->decimal('tax', 20, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Mengembalikan ke integer (legacy).
     */
    public function down()
    {
        if (!Schema::hasTable('reimbursement_travel_details')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY idr_rate INT NOT NULL");
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY tax INT NULL");
            return;
        }

        Schema::table('reimbursement_travel_details', function ($table) {
            $table->integer('idr_rate')->change();
            $table->integer('tax')->nullable()->change();
        });
    }
}
