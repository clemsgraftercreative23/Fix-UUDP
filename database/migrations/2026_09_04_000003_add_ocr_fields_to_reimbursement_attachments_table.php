<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOcrFieldsToReimbursementAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Result of comparing a receipt photo (OCR'd via Gemini's vision API)
     * against the No. Invoice/Receipt and Amount typed by the user, so
     * approvers can see what was verified without re-running OCR.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reimbursement_attachments')) {
            return;
        }
        Schema::table('reimbursement_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_attachments', 'ocr_status')) {
                $table->string('ocr_status', 20)->nullable()->after('file_hash')->index();
            }
            if (!Schema::hasColumn('reimbursement_attachments', 'ocr_extracted_no_invoice')) {
                $table->string('ocr_extracted_no_invoice')->nullable()->after('ocr_status');
            }
            if (!Schema::hasColumn('reimbursement_attachments', 'ocr_extracted_amount')) {
                $table->decimal('ocr_extracted_amount', 18, 2)->nullable()->after('ocr_extracted_no_invoice');
            }
            if (!Schema::hasColumn('reimbursement_attachments', 'ocr_message')) {
                $table->text('ocr_message')->nullable()->after('ocr_extracted_amount');
            }
            if (!Schema::hasColumn('reimbursement_attachments', 'ocr_checked_at')) {
                $table->timestamp('ocr_checked_at')->nullable()->after('ocr_message');
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
            foreach (['ocr_status', 'ocr_extracted_no_invoice', 'ocr_extracted_amount', 'ocr_message', 'ocr_checked_at'] as $column) {
                if (Schema::hasColumn('reimbursement_attachments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
