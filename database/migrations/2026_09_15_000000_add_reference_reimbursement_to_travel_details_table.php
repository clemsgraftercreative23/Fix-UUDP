<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceReimbursementToTravelDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a Travel cost-line row link to another (different user's) claim's
     * evidence by No. Invoice/Receipt instead of re-uploading the same
     * physical receipt -- e.g. two co-travelers sharing one hotel room where
     * only one of them has the Guest Folio. Nullable: unrelated to a row's
     * own upload/OCR flow, only set when the row was resolved via reference
     * instead of a file (see TravelReimbursementController::resolveReferencedRowInvoice()).
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_travel_details')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_travel_details', 'reference_reimbursement_id')) {
                    $table->unsignedInteger('reference_reimbursement_id')->nullable()->after('no_invoice');
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
        if (Schema::hasTable('reimbursement_travel_details') && Schema::hasColumn('reimbursement_travel_details', 'reference_reimbursement_id')) {
            Schema::table('reimbursement_travel_details', function (Blueprint $table) {
                $table->dropColumn('reference_reimbursement_id');
            });
        }
    }
}
