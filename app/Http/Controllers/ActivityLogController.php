<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function ensureAuthorized()
    {
        $role = Auth::user()->jabatan;
        if (!in_array($role, ['superadmin', 'Owner'], true)) {
            abort(403, 'Unauthorized');
        }
    }

    public function index()
    {
        $this->ensureAuthorized();

        return view('activity-log.index');
    }

    public function data(Request $request)
    {
        $this->ensureAuthorized();

        $query = ActivityLog::query()->orderBy('id', 'DESC');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('actor')) {
            $actor = trim($request->actor);
            $query->where(function ($sub) use ($actor) {
                $sub->where('actor_name', 'like', '%' . $actor . '%')
                    ->orWhere('actor_role', 'like', '%' . $actor . '%')
                    ->orWhere('reference_no', 'like', '%' . $actor . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->ajax()) {
            return datatables()
                ->of($query)
                ->editColumn('created_at', function ($item) {
                    return optional($item->created_at)->format('Y-m-d H:i:s');
                })
                ->addColumn('actor', function ($item) {
                    $name = $item->actor_name ?: '-';
                    $role = $item->actor_role ?: '-';
                    return $name . ' (' . $role . ')';
                })
                ->addColumn('reference', function ($item) {
                    return $item->reference_no ?: '-';
                })
                ->rawColumns([])
                ->make(true);
        }

        return response()->json([]);
    }
}
