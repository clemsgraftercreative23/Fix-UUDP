<?php

namespace App\Http\Controllers;

use App\Reimbursement;
use App\Support\ApprovalRollback;
use App\Support\JabatanClassifier;
use Illuminate\Http\Request;

/**
 * Setting > Rollback Approval: one place for a superadmin to find a claim and
 * move it back to an earlier approval step (the action itself is
 * ReimbursementController@rollback, shared with the detail pages).
 */
class RollbackApprovalSettingController extends Controller
{
    /** Statuses ApprovalRollback can move back from. */
    const ROLLBACKABLE_STATUSES = [1, 2, 11, 3];

    const TYPE_LABELS = [1 => 'Driver', 2 => 'Travel', 3 => 'Entertainment', 4 => 'Medical'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!JabatanClassifier::canRollbackApproval(auth()->user()->jabatan ?? null)) {
            abort(403);
        }

        $query = Reimbursement::query()
            ->with('user')
            ->whereIn('status', self::ROLLBACKABLE_STATUSES)
            ->orderBy('id', 'DESC');

        if ($request->filled('type')) {
            $query->where('reimbursement_type', (int) $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('no_reimbursement', 'like', '%' . $term . '%')
                    ->orWhere('created_by', 'like', '%' . $term . '%')
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', '%' . $term . '%');
                    });
            });
        }

        $claims = $query->paginate(25)->appends($request->only(['q', 'type', 'status']));

        $rows = $claims->getCollection()->map(function ($claim) {
            $targets = [];
            foreach (ApprovalRollback::allowedTargets($claim->status, $claim->reimbursement_type, $claim->menyetujui_finance_supervisor) as $target) {
                $targets[] = ['value' => $target, 'label' => 'Menunggu ' . ApprovalRollback::label($target)];
            }

            return (object) [
                'id' => $claim->id,
                'no' => $claim->no_reimbursement,
                'type' => self::TYPE_LABELS[(int) $claim->reimbursement_type] ?? '-',
                'applicant' => $claim->applicantDisplayName(),
                'amount' => $claim->nominal_pengajuan,
                'stage' => $this->stageLabel((int) $claim->status),
                'targets' => $targets,
                'detailUrl' => url($this->detailPath($claim->reimbursement_type) . $claim->id),
            ];
        });

        return view('settings.rollback-approval', [
            'claims' => $claims,
            'rows' => $rows,
            'typeLabels' => self::TYPE_LABELS,
            'filters' => $request->only(['q', 'type', 'status']),
        ]);
    }

    private function stageLabel($status)
    {
        if ($status === ApprovalRollback::STATUS_FINAL_APPROVED) {
            return 'Disetujui Finance (siap pencairan)';
        }

        return 'Menunggu ' . ApprovalRollback::label($status);
    }

    private function detailPath($type)
    {
        switch ((int) $type) {
            case 1:
                return '/reimbursement-driver/';
            case 2:
                return '/reimbursement-travel/';
            case 4:
                return '/reimbursement-medical/';
        }

        return '/reimbursement-entertaiment/';
    }
}
