<?php

namespace App\Http\Controllers;

use App\Reimbursement;
use App\ReimbursementTravel;
use Illuminate\Http\Request;

/**
 * Per-day "Additional Notes" an approver can leave on the Travel detail page.
 */
class TravelApprovalNoteController extends Controller
{
    /** Statuses where a claim is waiting on an approver. */
    const OPEN_STATUSES = [0, 1, 2, 11];

    const APPROVER_ROLES = ['Direktur Operasional', 'Finance', 'HR', 'HR GA', 'Finance Supervisor', 'Finance Manager', 'Owner', 'superadmin', 'admin'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function update(Request $request, $dayId)
    {
        $this->validate($request, ['approval_notes' => 'nullable|string|max:2000']);

        $day = ReimbursementTravel::findOrFail($dayId);
        $claim = Reimbursement::findOrFail($day->reimbursement_id);
        $user = auth()->user();
        $isSuperadmin = in_array($user->jabatan, ['superadmin', 'admin'], true);

        if (!in_array($user->jabatan, self::APPROVER_ROLES, true)
            || !in_array((int) $claim->status, self::OPEN_STATUSES, true)
            || (!$isSuperadmin && (int) $claim->id_user === (int) $user->id)) {
            abort(403);
        }

        $note = trim((string) $request->input('approval_notes'));
        $day->approval_notes = $note === '' ? null : $note;
        $day->approval_notes_by = $note === '' ? null : $user->name;
        $day->approval_notes_at = $note === '' ? null : now();
        $day->save();

        return back()->with('success', 'Additional notes saved.');
    }
}
