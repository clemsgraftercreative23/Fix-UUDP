<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceReimbursementToTravelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Same idea as reimbursement_travel_details.reference_reimbursement_id
     * (see 2026_09_15_000000_add_reference_reimbursement_to_travel_details_table.php)
     * but at day level: since evidence/No. Invoice moved from per expense-line
     * row to once per day (Sep 2026 redesign), the "use a co-traveler's
     * invoice instead of uploading" choice is now made once per day (Step 1),
     * not per row.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('reimbursement_travel')) {
            Schema::table('reimbursement_travel', function (Blueprint $table) {
                if (!Schema::hasColumn('reimbursement_travel', 'reference_reimbursement_id')) {
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
        if (Schema::hasTable('reimbursement_travel') && Schema::hasColumn('reimbursement_travel', 'reference_reimbursement_id')) {
            Schema::table('reimbursement_travel', function (Blueprint $table) {
                $table->dropColumn('reference_reimbursement_id');
            });
        }
    }
}
