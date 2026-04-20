<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksToReimbursementTravelDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reimbursement_travel_details', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_travel_details', 'remarks')) {
                $table->text('remarks')->nullable()->after('destination');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reimbursement_travel_details', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_travel_details', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
}
