<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approvers need somewhere to leave a per-day remark on the Travel detail page
 * (e.g. "hotel DP paid directly, checked against folio") without editing the
 * claimant's own purpose/remarks fields.
 */
class AddApprovalNotesToReimbursementTravelTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('reimbursement_travel', 'approval_notes')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            $table->text('approval_notes')->nullable();
            $table->string('approval_notes_by', 191)->nullable();
            $table->timestamp('approval_notes_at')->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('reimbursement_travel', 'approval_notes')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            $table->dropColumn(['approval_notes', 'approval_notes_by', 'approval_notes_at']);
        });
    }
}
