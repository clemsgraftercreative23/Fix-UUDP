<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccurateReverseFieldsToReimbursementTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement', 'accurate_record_id')) {
                $table->string('accurate_record_id', 40)->nullable()->after('accurate_sync_message');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_record_no')) {
                $table->string('accurate_record_no', 100)->nullable()->after('accurate_record_id');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_reversed_at')) {
                $table->dateTime('accurate_reversed_at')->nullable()->after('accurate_record_no');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement', 'accurate_reversed_at')) {
                $table->dropColumn('accurate_reversed_at');
            }
            if (Schema::hasColumn('reimbursement', 'accurate_record_no')) {
                $table->dropColumn('accurate_record_no');
            }
            if (Schema::hasColumn('reimbursement', 'accurate_record_id')) {
                $table->dropColumn('accurate_record_id');
            }
        });
    }
}
