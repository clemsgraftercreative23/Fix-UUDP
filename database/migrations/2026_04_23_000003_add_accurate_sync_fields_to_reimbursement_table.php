<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccurateSyncFieldsToReimbursementTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement', 'metode_bdc')) {
                $table->string('metode_bdc')->nullable()->after('metode_cash');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_payload_json')) {
                $table->longText('accurate_payload_json')->nullable()->after('akun_perkiraan');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_synced_at')) {
                $table->dateTime('accurate_synced_at')->nullable()->after('accurate_payload_json');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_sync_status')) {
                $table->string('accurate_sync_status', 20)->nullable()->after('accurate_synced_at');
            }
            if (!Schema::hasColumn('reimbursement', 'accurate_sync_message')) {
                $table->text('accurate_sync_message')->nullable()->after('accurate_sync_status');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('reimbursement')) {
            return;
        }

        Schema::table('reimbursement', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement', 'accurate_sync_message')) {
                $table->dropColumn('accurate_sync_message');
            }
            if (Schema::hasColumn('reimbursement', 'accurate_sync_status')) {
                $table->dropColumn('accurate_sync_status');
            }
            if (Schema::hasColumn('reimbursement', 'accurate_synced_at')) {
                $table->dropColumn('accurate_synced_at');
            }
            if (Schema::hasColumn('reimbursement', 'accurate_payload_json')) {
                $table->dropColumn('accurate_payload_json');
            }
            if (Schema::hasColumn('reimbursement', 'metode_bdc')) {
                $table->dropColumn('metode_bdc');
            }
        });
    }
}

