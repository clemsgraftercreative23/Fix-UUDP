<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoInvoiceToReimbursementDriverTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reimbursement_driver')) {
            return;
        }

        Schema::table('reimbursement_driver', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_driver', 'no_invoice')) {
                $table->string('no_invoice', 191)->nullable()->after('remark');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('reimbursement_driver')) {
            return;
        }

        Schema::table('reimbursement_driver', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_driver', 'no_invoice')) {
                $table->dropColumn('no_invoice');
            }
        });
    }
}
