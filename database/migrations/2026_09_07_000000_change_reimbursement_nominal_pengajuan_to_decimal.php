<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeReimbursementNominalPengajuanToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * Jaga nilai desimal (sen) pada nominal_pengajuan, sesuai perbaikan
     * amount desimal pada baris entertainment/travel.
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reimbursement MODIFY nominal_pengajuan DECIMAL(20,2) NOT NULL');
            return;
        }

        Schema::table('reimbursement', function ($table) {
            $table->decimal('nominal_pengajuan', 20, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Mengembalikan ke integer (legacy).
     */
    public function down()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE reimbursement MODIFY nominal_pengajuan BIGINT NOT NULL');
            return;
        }

        Schema::table('reimbursement', function ($table) {
            $table->bigInteger('nominal_pengajuan')->change();
        });
    }
}
