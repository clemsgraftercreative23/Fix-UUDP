<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalDatesToPengajuanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * pj_operasional/pj_finance/pj_owner only stored the approver's name,
     * so every approval level showed the same submission created_at date
     * in the settlement (Pertanggungjawaban) UI. Add a timestamp per level.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('pengajuan')) {
            return;
        }
        Schema::table('pengajuan', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan', 'pj_operasional_at')) {
                $table->timestamp('pj_operasional_at')->nullable()->after('pj_operasional');
            }
            if (!Schema::hasColumn('pengajuan', 'pj_finance_at')) {
                $table->timestamp('pj_finance_at')->nullable()->after('pj_finance');
            }
            if (!Schema::hasColumn('pengajuan', 'pj_owner_at')) {
                $table->timestamp('pj_owner_at')->nullable()->after('pj_owner');
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
        if (!Schema::hasTable('pengajuan')) {
            return;
        }
        Schema::table('pengajuan', function (Blueprint $table) {
            foreach (['pj_operasional_at', 'pj_finance_at', 'pj_owner_at'] as $column) {
                if (Schema::hasColumn('pengajuan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
