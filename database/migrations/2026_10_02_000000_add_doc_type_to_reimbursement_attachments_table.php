<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocTypeToReimbursementAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Whether an uploaded evidence file is the Invoice/Receipt the amount is
     * claimed from (read by OCR) or merely supporting proof -- an emailed
     * confirmation screenshot, a ticket, an assignment letter -- that backs
     * the activity up without any invoice to read.
     *
     * The create form already distinguished the two (reimburse[i][file_types][])
     * but only used it to skip OCR at submit time and then threw it away, so a
     * saved proof was indistinguishable from an invoice: approvers could not
     * tell them apart on the detail page, and re-saving in edit put every
     * stored file back through OCR as an invoice.
     *
     * Existing rows default to 'invoice', which is what they were treated as.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_attachments')) {
            return;
        }
        Schema::table('reimbursement_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_attachments', 'doc_type')) {
                $table->string('doc_type', 16)->default('invoice')->after('original_name')->index();
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
            if (Schema::hasColumn('reimbursement_attachments', 'doc_type')) {
                $table->dropColumn('doc_type');
            }
        });
    }
}
