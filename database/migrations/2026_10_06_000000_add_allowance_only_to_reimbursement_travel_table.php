<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "No expense details -- Travel Allowance only" was a transient form state with
 * no column behind it: on reload the controller re-derived it as "no evidence
 * AND at least one expense row AND every amount zero".
 *
 * That guess cannot hold. Ticking the box clears the expense rows, and rows
 * without a cost type are skipped when saving, so the day comes back with no
 * detail rows at all -- the "at least one expense row" half of the condition
 * fails and the checkbox silently un-ticks itself after saving a draft.
 *
 * Storing the user's actual choice removes the guesswork.
 */
class AddAllowanceOnlyToReimbursementTravelTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('reimbursement_travel', 'allowance_only')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            $table->boolean('allowance_only')->default(false)->after('allowance');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('reimbursement_travel', 'allowance_only')) {
            return;
        }

        Schema::table('reimbursement_travel', function (Blueprint $table) {
            $table->dropColumn('allowance_only');
        });
    }
}
