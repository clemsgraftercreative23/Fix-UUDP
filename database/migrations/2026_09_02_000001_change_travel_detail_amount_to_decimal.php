<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeTravelDetailAmountToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * Menjaga nilai desimal pada nominal Amount (sesuai bukti/invoice asli),
     * setara dengan migrasi idr_rate/tax sebelumnya.
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_travel_details')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY amount DECIMAL(20,2) NOT NULL");
            return;
        }

        // Fallback untuk driver lain yang mendukung schema change.
        Schema::table('reimbursement_travel_details', function ($table) {
            $table->decimal('amount', 20, 2)->change();
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
            DB::statement("ALTER TABLE reimbursement_travel_details MODIFY amount INT NOT NULL");
            return;
        }

        Schema::table('reimbursement_travel_details', function ($table) {
            $table->integer('amount')->change();
        });
    }
}
