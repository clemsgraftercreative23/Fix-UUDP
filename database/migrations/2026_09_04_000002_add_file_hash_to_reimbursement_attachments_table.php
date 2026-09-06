<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileHashToReimbursementAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * SHA-256 of each uploaded evidence file's bytes, used to catch the
     * same receipt photo being reused across submissions without asking
     * the user to type an invoice/receipt number.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_attachments')) {
            return;
        }
        Schema::table('reimbursement_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_attachments', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('file_size')->index();
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
        if (!Schema::hasTable('reimbursement_attachments')) {
            return;
        }
        Schema::table('reimbursement_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_attachments', 'file_hash')) {
                $table->dropColumn('file_hash');
            }
        });
    }
}
