<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchantNameToTravelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Evidence/No. Invoice for Travel moved from per expense-line row to
     * once per day (see TravelReimbursementController::store()/updateInquiry()) --
     * this stores the OCR-read Merchant/Hotel name alongside the day's
     * existing `no_invoice` column, purely for display/reference in the
     * "OCR Result (Auto-filled)" summary panel.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_travel')) {
            Schema::table('reimbursement_travel', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_travel', 'merchant_name')) {
                    $table->string('merchant_name', 255)->nullable()->after('no_invoice');
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
        if (Schema::hasTable('reimbursement_travel') && Schema::hasColumn('reimbursement_travel', 'merchant_name')) {
            Schema::table('reimbursement_travel', function (Blueprint $table) {
                $table->dropColumn('merchant_name');
            });
        }
    }
}
