<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoInvoiceToTravelDetailsAndEntertaimentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * No. Invoice/Receipt moves from manual entry (day-level for Travel,
     * submission-level for Entertainment) to OCR-derived, per cost-line
     * row -- each row's own uploaded receipt now supplies its own number.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_travel_details')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_travel_details', 'no_invoice')) {
                    $table->string('no_invoice', 191)->nullable()->after('destination');
                }
            });
        }

        if (Schema::hasTable('reimbursement_entertaiments')) {
            Schema::table('reimbursement_entertaiments', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_entertaiments', 'no_invoice')) {
                    $table->string('no_invoice', 191)->nullable();
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
        if (Schema::hasTable('reimbursement_travel_details') && Schema::hasColumn('reimbursement_travel_details', 'no_invoice')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                $table->dropColumn('no_invoice');
            });
        }

        if (Schema::hasTable('reimbursement_entertaiments') && Schema::hasColumn('reimbursement_entertaiments', 'no_invoice')) {
            Schema::table('reimbursement_entertaiments', function (Blueprint $table) {
                $table->dropColumn('no_invoice');
            });
        }
    }
}
