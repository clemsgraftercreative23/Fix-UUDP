<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksToReimbursementTravelDetailsTable extends Migration
{
    /**
     * Per-cost-line free-text note (mis. "Hotel for 18-20 Aug 2026" saat
     * hotel/tiket PP multi-hari digabung jadi satu baris klaim). Berbeda
     * dari `reimbursement.remark` (satu untuk seluruh submission) dan dari
     * `destination` (keterangan tujuan/rute).
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_travel_details')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_travel_details', 'remarks')) {
                    $table->string('remarks', 191)->nullable()->after('destination');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('reimbursement_travel_details') && Schema::hasColumn('reimbursement_travel_details', 'remarks')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }
    }
}
