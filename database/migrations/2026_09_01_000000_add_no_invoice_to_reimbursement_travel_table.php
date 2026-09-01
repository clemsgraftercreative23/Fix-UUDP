<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoInvoiceToReimbursementTravelTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reimbursement_travel')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_travel', 'no_invoice')) {
                $table->string('no_invoice', 191)->nullable()->after('purpose');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('reimbursement_travel')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_travel', 'no_invoice')) {
                $table->dropColumn('no_invoice');
            }
        });
    }
}
