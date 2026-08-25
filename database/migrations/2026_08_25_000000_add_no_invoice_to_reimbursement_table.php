<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoInvoiceToReimbursementTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement', 'no_invoice')) {
                $table->string('no_invoice', 191)->nullable()->after('no_reimbursement');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement', 'no_invoice')) {
                $table->dropColumn('no_invoice');
            }
        });
    }
}
