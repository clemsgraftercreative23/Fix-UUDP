<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\ReimbursementDetail;
use App\ReimbursementDriver;
use App\ReimbursementAttachment;
use App\User;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use App\Support\ActivityLogger;
class DriverReimbursementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function attachmentTableReady(): bool
    {
        return Schema::hasTable('reimbursement_attachments');
    }

    private function storeAttachmentFile(?UploadedFile $file): string
    {
        if (!$file) {
            return '';
        }

        $targetDir = public_path('images/file_bukti');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = 'jpg';
        }

        $filename = uniqid('bukti_', true) . '.' . $ext;
        $file->move($targetDir, $filename);

        return $filename;
    }

    private function getDriverRowUploadedFiles(Request $request, int $index): array
    {
        $files = [];
        $f = data_get($request->file('file'), $index);
        if ($f instanceof UploadedFile) {
            $files[] = $f;
        }

        $p = data_get($request->file('proof'), $index);
        if ($p instanceof UploadedFile) {
            $files[] = $p;
        }

        $batch = data_get($request->file('attachments'), $index);
        if ($batch instanceof UploadedFile) {
            $files[] = $batch;
        } elseif (is_array($batch)) {
            foreach ($batch as $bf) {
                if ($bf instanceof UploadedFile) {
                    $files[] = $bf;
                }
            }
        }

        return $files;
    }

    private function ensureDriverLegacyAttachment(int $reimbursementId, int $detailId, string $legacyEvidence): void
    {
        if (!$this->attachmentTableReady()) {
            return;
        }
        $legacyEvidence = trim($legacyEvidence);
        if ($detailId <= 0 || $legacyEvidence === '') {
            return;
        }

        $exists = ReimbursementAttachment::where('detail_type', 'reimbursement_driver')
            ->where('detail_id', $detailId)
            ->where('file_name', $legacyEvidence)
            ->exists();
        if ($exists) {
            return;
        }

        ReimbursementAttachment::create([
            'reimbursement_id' => $reimbursementId,
            'module' => 'driver',
            'detail_type' => 'reimbursement_driver',
            'detail_id' => $detailId,
            'file_name' => $legacyEvidence,
            'original_name' => $legacyEvidence,
            'created_by' => auth()->id(),
        ]);
    }

    private function syncDriverAttachments(Request $request, int $rowIndex, int $reimbursementId, int $newDetailId, int $oldDetailId = 0, string $legacyEvidence = ''): array
    {
        if (!$this->attachmentTableReady()) {
            $uploaded = $this->getDriverRowUploadedFiles($request, $rowIndex);
            if (!empty($uploaded)) {
                $first = $this->storeAttachmentFile($uploaded[0]);
                return $first === '' ? [] : [$first];
            }
            $legacyEvidence = trim((string) $legacyEvidence);
            return $legacyEvidence === '' ? [] : [$legacyEvidence];
        }

        $kept = [];

        if ($oldDetailId > 0) {
            $this->ensureDriverLegacyAttachment($reimbursementId, $oldDetailId, $legacyEvidence);

            $oldRows = ReimbursementAttachment::where('detail_type', 'reimbursement_driver')
                ->where('detail_id', $oldDetailId)
                ->orderBy('id')
                ->get();

            $hasKeepField = $request->has('keep_attachment_ids.' . $rowIndex);
            $keepIds = $hasKeepField
                ? collect((array) data_get($request->input('keep_attachment_ids', []), $rowIndex, []))
                    ->map(function ($v) { return (int) $v; })
                    ->filter(function ($v) { return $v > 0; })
                    ->values()
                : null;

            foreach ($oldRows as $old) {
                if ($keepIds !== null && !$keepIds->contains((int) $old->id)) {
                    continue;
                }

                ReimbursementAttachment::create([
                    'reimbursement_id' => $reimbursementId,
                    'module' => 'driver',
                    'detail_type' => 'reimbursement_driver',
                    'detail_id' => $newDetailId,
                    'file_name' => $old->file_name,
                    'original_name' => $old->original_name,
                    'mime_type' => $old->mime_type,
                    'file_size' => (int) $old->file_size,
                    'created_by' => auth()->id(),
                ]);
                $kept[] = (string) $old->file_name;
            }
        }

        $newNames = [];
        foreach ($this->getDriverRowUploadedFiles($request, $rowIndex) as $file) {
            $originalName = '';
            $mimeType = null;
            $fileSize = 0;
            try {
                $originalName = (string) $file->getClientOriginalName();
            } catch (\Throwable $e) {
                $originalName = '';
            }
            try {
                $mimeType = $file->getClientMimeType();
            } catch (\Throwable $e) {
                $mimeType = null;
            }
            try {
                $sizeCandidate = $file->getSize();
                $fileSize = is_numeric($sizeCandidate) ? (int) $sizeCandidate : 0;
            } catch (\Throwable $e) {
                $fileSize = 0;
            }

            $stored = $this->storeAttachmentFile($file);
            if ($stored === '') {
                continue;
            }
            ReimbursementAttachment::create([
                'reimbursement_id' => $reimbursementId,
                'module' => 'driver',
                'detail_type' => 'reimbursement_driver',
                'detail_id' => $newDetailId,
                'file_name' => $stored,
                'original_name' => ($originalName !== '' ? $originalName : $stored),
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'created_by' => auth()->id(),
            ]);
            $newNames[] = $stored;
        }

        return array_values(array_filter(array_merge($kept, $newNames)));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id_user = auth()->user()->id;

        if (request()->ajax()) {
            if (auth()->user()->jabatan == 'superadmin') {
                $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                    ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                    ->where('reimbursement.reimbursement_type', 1);
            } else {
                $status = $request->status;
                if ($status == null) {
                    $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                        ->leftJoin('users', 'users.id', 'reimbursement.id_user')
                        ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                        ->where('reimbursement.reimbursement_type', 1)
                        ->where('reimbursement.id_user', $id_user);
                } else {
                    $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                        ->leftJoin('users', 'users.id', 'reimbursement.id_user')
                        ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                        ->where('reimbursement.reimbursement_type', 1)
                        ->where('reimbursement.status', $request->status)
                        ->where('reimbursement.id_user', $id_user);
                }
            }

            if (isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->first);
            }

            if (isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->last);
            }

            if (isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status', $request->status);
            }

            if (isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user', '=', $request->driver);
            }

            if (isset($request->payment_type) && $request->payment_type != "" && $request->payment_type != "ALL") {
                $paymentType = $request->payment_type;
                $data = $data->whereExists(function ($query) use ($paymentType) {
                    $query->select(DB::raw(1))
                        ->from('reimbursement_driver')
                        ->whereColumn('reimbursement_driver.reimbursement_id', 'reimbursement.id')
                        ->where('reimbursement_driver.payment_type', $paymentType);
                });
            }

            if (auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()
                ->of($data)
                ->addColumn('status_label', function ($data) {
                    if ($data->status == 0) {
                        $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                    } elseif ($data->status == 1) {
                        $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                    } elseif ($data->status == 2) {
                        $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                    } elseif ($data->status == 3) {
                        $button = '<button  class=" view btn btn-success btn-sm">PROCESS SETTLEMET</button>';
                    } elseif ($data->status == 4) {
                        $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                    } elseif ($data->status == 5) {
                        $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                    } elseif ($data->status == 9) {
                        if ($data->mengetahui_op == '-') {
                            $meng = 'HEAD DEPT';
                        } elseif ($data->mengetahui_finance == '-') {
                            $meng = 'HR GA';
                        } elseif ($data->mengetahui_owner == '-') {
                            $meng = 'FINANCE';
                        }
                        $button = '<button  class="view btn btn-danger btn-sm">REJECTED ' . $meng . '</button>';
                    } elseif ($data->status == 10) {
                        $button = '<button  class="view btn btn-warning btn-sm">DRAFT</button>';
                    } 

                    return $button;
                })
                ->addColumn('action', function ($data) {
                    $buttons = '<div style="display:flex; gap:4px; align-items:center;">';

                    // Show button (always visible)
                    $buttons .= '<a href="' . route('reimbursement-driver.show', $data->id) . '" class="btn btn-info btn-sm" title="Detail" aria-label="Detail"><i class="fa fa-eye"></i></a>';

                    // Edit button (always visible)
                    $buttons .= '<a href="' . route('reimbursement-driver.edit', $data->id) . '" class="btn btn-primary btn-sm" title="Edit" aria-label="Edit"><i class="fa fa-edit"></i></a>';

                    // Delete button (only for status 0 or 10)
                    if (in_array((int) $data->status, [0, 10], true)) {
                        $buttons .= '<form method="POST" action="' . route('reimbursement-driver.destroy', $data->id) . '" style="display:inline-block; margin:0;" onsubmit="return confirm(\'Yakin ingin menghapus pengajuan ini?\')">'
                            . csrf_field()
                            . method_field('DELETE')
                            . '<button type="submit" class="btn btn-danger btn-sm" title="Delete" aria-label="Delete"><i class="fa fa-trash"></i></button></form>';
                    } else {
                        $buttons .= '<span>-</span>';
                    }

                    $buttons .= '</div>';

                    return $buttons;
                })
                ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
                })
                ->editColumn('no_project', function ($data) {
                    return $data->user->name;
                })
                ->addColumn('nominal_pengajuan', function ($data) {
                    $button = '';
                    $button .= number_format($data->nominal_pengajuan, 0, ',', '.');
                    return $button;
                })
                ->addColumn('payment_type', function ($data) {
                    $types = DB::table('reimbursement_driver')
                        ->where('reimbursement_id', $data->id)
                        ->whereNotNull('payment_type')
                        ->distinct()
                        ->pluck('payment_type')
                        ->toArray();
                    return count($types) ? implode(', ', $types) : '-';
                })
                ->editColumn('no_reimbursement', function ($data) {
                    return $data->no_reimbursement;
                })
                ->rawColumns(['status_label', 'action', 'checkbox', 'nominal_pengajuan', 'payment_type'])
                ->make(true);
        }

        $check_approval = DB::select(DB::raw("SELECT count(id) AS id FROM users WHERE id_approval = '$id_user'"))['0']->id;

        return view('reimbursement-driver.index', [
            'check_approval' => $check_approval,
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn(
                'id',
                Reimbursement::select('id_user')
                    ->get()
                    ->pluck('id_user')
            )->get(),
        ]);
    }

    public function approval(Request $request)
    {
        if (request()->ajax()) {
            $id_user = auth()->user()->id;

            if (auth()->user()->jabatan == 'Finance' || auth()->user()->jabatan == 'Finance Supervisor' || auth()->user()->jabatan == 'Owner' || auth()->user()->jabatan == 'superadmin' || auth()->user()->jabatan == 'Direktur Operasional') {
                $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                    ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                    ->where('reimbursement.reimbursement_type', 1)->where('reimbursement.status', '!=',10);
            } else {
                $status = $request->status;
                if ($status == null) {
                    $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                        ->leftJoin('users', 'users.id', 'reimbursement.id_user')
                        ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                        ->where('reimbursement.reimbursement_type', 1)
                        ->where('users.id_approval', $id_user)->where('reimbursement.status', '!=',10);
                } else {
                    $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                        ->leftJoin('users', 'users.id', 'reimbursement.id_user')
                        ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan')
                        ->where('reimbursement.reimbursement_type', 1)
                        ->where('reimbursement.status', $request->status)
                        ->where('users.id_approval', $id_user)->where('reimbursement.status', '!=',10);
                }
            }

            if (isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->first);
            }

            if (isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->last);
            }

            if (isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status', $request->status);
            }

            if (isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user', '=', $request->driver);
            }

            if (isset($request->payment_type) && $request->payment_type != "" && $request->payment_type != "ALL") {
                $paymentType = $request->payment_type;
                $data = $data->whereExists(function ($query) use ($paymentType) {
                    $query->select(DB::raw(1))
                        ->from('reimbursement_driver')
                        ->whereColumn('reimbursement_driver.reimbursement_id', 'reimbursement.id')
                        ->where('reimbursement_driver.payment_type', $paymentType);
                });
            }

            if (auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()
                ->of($data)
                ->addColumn('action', function ($data) {
                    if ($data->status == 0) {
                        $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                    } elseif ($data->status == 1) {
                        $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                    } elseif ($data->status == 2) {
                        $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                    } elseif ($data->status == 3) {
                        $button = '<button  class=" view btn btn-success btn-sm">PROCESS SETTLEMET</button>';
                    } elseif ($data->status == 4) {
                        $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                    } elseif ($data->status == 5) {
                        $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                    } elseif ($data->status == 9) {
                        if ($data->mengetahui_op == '-') {
                            $meng = 'HEAD DEPT';
                        } elseif ($data->mengetahui_finance == '-') {
                            $meng = 'HR GA';
                        } elseif ($data->mengetahui_owner == '-') {
                            $meng = 'FINANCE';
                        } else {
                            $meng = '';
                        }
                        $button = '<button  class="view btn btn-danger btn-sm">REJECTED ' . $meng . '</button>';
                    }
                    $button .= '&nbsp;&nbsp;';

                    return $button;
                })
                ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
                })
                ->editColumn('no_project', function ($data) {
                    //return $data->user->name;
                  	return $data->created_by;
                })
                ->addColumn('nominal_pengajuan', function ($data) {
                    $button = '';
                    $button .= number_format($data->nominal_pengajuan, 0, ',', '.');
                    return $button;
                })
                ->addColumn('payment_type', function ($data) {
                    $types = DB::table('reimbursement_driver')
                        ->where('reimbursement_id', $data->id)
                        ->whereNotNull('payment_type')
                        ->distinct()
                        ->pluck('payment_type')
                        ->toArray();
                    return count($types) ? implode(', ', $types) : '-';
                })
                ->editColumn('no_reimbursement', function ($data) {
                    return "<a href='" . route('reimbursement-driver.show', $data->id) . "'>" . $data->no_reimbursement . "</a>";
                })
                ->rawColumns(['action', 'checkbox', 'nominal_pengajuan', 'payment_type', 'no_reimbursement'])
                ->make(true);
        }

        return view('reimbursement-driver.approval', [
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn(
                'id',
                Reimbursement::select('id_user')
                    ->get()
                    ->pluck('id_user')
            )->get(),
        ]);
    }

    public function destroy($id)
    {
        $data = Reimbursement::where('id', $id)
            ->where('reimbursement_type', 1)
            ->first();

        if (!$data) {
            return redirect()->back()->withErrors(['Data reimbursement driver tidak ditemukan']);
        }

        $isOwner = (int) $data->id_user === (int) auth()->id();
        $isSuperadmin = auth()->user()->jabatan === 'superadmin';
        if (!$isOwner && !$isSuperadmin) {
            return redirect()->back()->withErrors(['Anda tidak memiliki akses untuk menghapus data ini']);
        }

        if (!in_array((int) $data->status, [0, 10], true)) {
            return redirect()->back()->withErrors(['Hanya pengajuan dengan status pending atau draft yang dapat dihapus']);
        }

        DB::beginTransaction();
        try {
            ActivityLogger::log(
                'reimbursement-driver',
                'delete',
                'Reimbursement driver dihapus',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => $data->status]
            );
            ReimbursementDriver::where('reimbursement_id', $id)->delete();
            $data->delete();
            DB::commit();

            return redirect('reimbursement-driver')->with(['success' => 'Pengajuan berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['Gagal menghapus pengajuan: ' . $e->getMessage()]);
        }
    }

    public function getReimbursement($id)
    {
        $data = DB::select(DB::raw("SELECT * FROM reimbursement WHERE id='$id'"));
        $detail = DB::select(DB::raw("SELECT * FROM reimbursement_driver WHERE reimbursement_id='$id'"));
        $department = DB::select(DB::raw("SELECT * FROM departemen"));

        return view('reimbursement-driver.popup', compact('data', 'detail', 'department'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        $id_max = DB::select(DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id + 1;
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else {
            $status = 10; // DRAFT
            $notif = 'Reimbursement Successfully Saved as Draft';
        }
        try {
            $data = [
                "id_user" => auth()->user()->id,
                // "no_reimbursement" => "UUDP-REIMBURSE-D-00".(Reimbursement::count()+1),
                "no_reimbursement" => "UUDP-REIMBURSE-D-00" . $id_max,
                "date" => $request->date,
                "reimbursement_department_id" => $request->reimbursement_department_id,
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => str_replace(".", "", $request->total_pengajuan),
                "status" => $status,
                "reimbursement_type" => 1,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark_parent,
            ];

            $data = Reimbursement::create($data);
            ActivityLogger::log(
                'reimbursement-driver',
                $status == 10 ? 'draft' : 'create',
                $status == 10 ? 'Reimbursement driver disimpan sebagai draft' : 'Reimbursement driver dibuat',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => $status]
            );
            for ($i = 0; $i < count($request->toll); $i++) {
                $payload = [
                    'reimbursement_id' => $data->id,
                    'toll' => isset($request->toll[$i]) ? str_replace(".", "", $request->toll[$i]) : 0,
                    'gasoline' => isset($request->gasoline[$i]) ? str_replace(".", "", $request->gasoline[$i]) : 0,
                    'parking' => isset($request->parking[$i]) ? str_replace(".", "", $request->parking[$i]) : 0,
                    'others' => isset($request->others[$i]) ? str_replace(".", "", $request->others[$i]) : 0,
                    'subtotal' => isset($request->total[$i]) ? str_replace(".", "", $request->total[$i]) : 0,
                    'remark' => isset($request->remark[$i]) ? str_replace(".", "", $request->remark[$i]) : null,
                    'payment_type' => isset($request->payment_type[$i]) ? str_replace(".", "", $request->payment_type[$i]) : null,
                ];
                $payload['evidence'] = '';
                $dt = ReimbursementDriver::create($payload);

                $allAttachmentNames = $this->syncDriverAttachments($request, $i, (int) $data->id, (int) $dt->id);
                $dt->evidence = $allAttachmentNames[0] ?? '';
                $dt->save();
            }

            $user = \App\User::where('id', $data->id_user)->first();

            if ($status != 10) {
            
                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $user->name .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                            \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])->post();

                $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                        $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                        })->get();

                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

                if (!empty($approval)) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                        ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                        ->withData([
                            'target' => $approval[0]->phoneNumber,
                            'message' =>
                                "Hai *" .
                                $approval[0]->name .
                                "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                                $data->no_reimbursement .
                                "* sebesar *Rp " .
                                number_format($data->nominal_pengajuan, 0, ',', '.') .
                                "* telah diterima.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                                url('/reimbursement-driver/' . $data->id),
                        ])->post();
                }
            }
            

            DB::commit();
            return redirect()
                ->back()
                ->with(['success' => $notif]);
        } catch (\Exception $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line " . $e->getLine());
            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line " . $e->getLine());

            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $data = Reimbursement::find($id);
        $meng = '';
        $reim = DB::select(DB::raw("SELECT * FROM reimbursement WHERE id='$id'"));
        $id_pengaju = $reim['0']->id_user;
        $name = DB::select(DB::raw("SELECT name FROM users WHERE id='$id_pengaju'"))['0']->name;
        $detail = DB::select(DB::raw("SELECT * FROM reimbursement_driver WHERE reimbursement_id='$id'"));

        $metode_cash_ = $reim['0']->metode_cash;
        if ($metode_cash_ == null) {
            $metode_cash = "";
        } else {
            $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
        }

        return view('reimbursement-driver.detail', [
            'data' => $data,
            'meng' => $meng,
            'reim' => $reim,
            'detail' => $detail,
            'name' => $name,
            'metode_cash' => $metode_cash,
        ]);
    }

    public function edit($id)
    {
        // Display the detail view which contains the editable form with all functionalities
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $payload = [
                    "id_user" => auth()->user()->id,
                    // "no_reimbursement" => "UUDP-REIMBURSE-D-00".(Reimbursement::count()+1),
                    "date" => $request->date,
                    "reimbursement_department_id" => $request->reimbursement_department_id,
                    "mengetahui_op" => "-",
                    "mengetahui_finance" => "-",
                    "mengetahui_owner" => "-",
                    "nominal_pengajuan" => str_replace(".", "", $request->total_pengajuan),
                    "status" => 0,
                    "reimbursement_type" => 1,
                    "created_by" => auth()->user()->name,
                    "remark" => $request->remark_parent,
                ];

            $data = Reimbursement::find($id);

            $data->update($payload);
            ActivityLogger::log(
                'reimbursement-driver',
                'update',
                'Reimbursement driver diperbaharui',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => $data->status]
            );

            DB::select(DB::raw("UPDATE reimbursement_driver SET status=0  WHERE reimbursement_id = '$id'"));

            for ($i = 0; $i < count($request->toll); $i++) {
                $oldDetailId = isset($request->id_detail[$i]) && ctype_digit((string) $request->id_detail[$i])
                    ? (int) $request->id_detail[$i]
                    : 0;
                $legacyEvidence = '';
                if ($oldDetailId > 0) {
                    $rowEv = DB::select(DB::raw("SELECT evidence FROM reimbursement_driver WHERE id='$oldDetailId'"));
                    $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
                }

                $new = new ReimbursementDriver();
                $new->reimbursement_id = $data->id;
                $new->toll = str_replace(".", "", $request->toll[$i]);
                $new->gasoline = str_replace(".", "", $request->gasoline[$i]);
                $new->parking = str_replace(".", "", $request->parking[$i]);
                $new->others = str_replace(".", "", $request->others[$i]);
                $new->subtotal = str_replace(".", "", $request->total[$i]);
                $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                $new->remark = $request->remark[$i];
                $new->evidence = '';
                $new->status = 1;
                $new->save();

                $allAttachmentNames = $this->syncDriverAttachments(
                    $request,
                    $i,
                    (int) $data->id,
                    (int) $new->id,
                    $oldDetailId,
                    $legacyEvidence
                );
                $new->evidence = $allAttachmentNames[0] ?? '';
                $new->save();
            }

            $delete = DB::select(DB::raw("DELETE FROM reimbursement_driver WHERE reimbursement_id = '$id' AND status=0"));

            $user = \App\User::where('id', $data->id_user)->first();
            
            $curl = \Curl::to('https://api.fonnte.com/send')
                ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                ->withData([
                    'target' => $user->phoneNumber,
                    'message' =>
                        "Hai *" .
                        $user->name .
                        "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                        $data->no_reimbursement .
                        "* sebesar *Rp " .
                        number_format($data->nominal_pengajuan, 0, ',', '.') .
                        "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                        \n\nKlik untuk melihat detail pengajuan : " .
                        url('/reimbursement-driver/' . $data->id),
                ])->post();

            $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                    $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                    })->get();

            $id_approval  = $user->id_approval;
            $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

            if (!empty($approval)) {
                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $approval[0]->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $approval[0]->name .
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])->post();
            }
            

            DB::commit();
            return redirect()
                ->back()
                ->with(['success' => 'Reimbursement Successfully Submitted']);
        } catch (\Exception $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line " . $e->getLine());
            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line " . $e->getLine());

            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        }
    }

    public function updateApproval(Request $request, $id)
    {
        DB::beginTransaction();
      
		$cek_iduser = DB::select(DB::raw("SELECT id_user FROM reimbursement WHERE id='$id'"))['0']->id_user;
        
		if(auth()->user()->id == $cek_iduser) {
          
          if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
          } else if (isset($_POST['save_draft'])) {
              $status = 10; 
              $notif = 'Reimbursement Successfully Saved as Draft';
          } 

          try {
              $payload = [
                  "date" => $request->date,
                  "reimbursement_department_id" => $request->reimbursement_department_id,
                  "nominal_pengajuan" => str_replace(".", "", $request->total_pengajuan),
                  "remark" => $request->remark_parent,
                  "status" => $status,
              ];

              $data = Reimbursement::find($id);

              $data->update($payload);
              ActivityLogger::log(
                  'reimbursement-driver',
                  $status == 10 ? 'draft' : 'update',
                  $status == 10 ? 'Reimbursement driver diperbaharui sebagai draft' : 'Reimbursement driver diperbaharui',
                  $data->no_reimbursement,
                  'reimbursement',
                  $data->id,
                  ['status' => $status]
              );

              DB::select(DB::raw("UPDATE reimbursement_driver SET status=0  WHERE reimbursement_id = '$id'"));

              for ($i = 0; $i < count($request->toll); $i++) {
                  $oldDetailId = isset($request->id_detail[$i]) && ctype_digit((string) $request->id_detail[$i])
                      ? (int) $request->id_detail[$i]
                      : 0;
                  $legacyEvidence = '';
                  if ($oldDetailId > 0) {
                      $rowEv = DB::select(DB::raw("SELECT evidence FROM reimbursement_driver WHERE id='$oldDetailId'"));
                      $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
                  }

                  $new = new ReimbursementDriver();
                  $new->reimbursement_id = $data->id;
                  $new->toll = str_replace(".", "", $request->toll[$i]);
                  $new->gasoline = str_replace(".", "", $request->gasoline[$i]);
                  $new->parking = str_replace(".", "", $request->parking[$i]);
                  $new->others = str_replace(".", "", $request->others[$i]);
                  $new->subtotal = str_replace(".", "", $request->total[$i]);
                  $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                  $new->remark = $request->remark[$i];
                  $new->evidence = '';
                  $new->status = 1;
                  $new->save();

                  $allAttachmentNames = $this->syncDriverAttachments(
                      $request,
                      $i,
                      (int) $data->id,
                      (int) $new->id,
                      $oldDetailId,
                      $legacyEvidence
                  );
                  $new->evidence = $allAttachmentNames[0] ?? '';
                  $new->save();
              }

              $delete = DB::select(DB::raw("DELETE FROM reimbursement_driver WHERE reimbursement_id = '$id' AND status=0"));

              DB::commit();

              return redirect()
                  ->back()
                  ->with(['success' => $notif]);
          } catch (\Exception $e) {
              // return var_dump($e);
              dd($e->getMessage() . " at line " . $e->getLine());
              DB::rollback();
              return redirect()
                  ->back()
                  ->withErrors(['Error ' . $e->getMessage()]);
          } catch (\Throwable $e) {
              // return var_dump($e);
              dd($e->getMessage() . " at line " . $e->getLine());

              DB::rollback();
              return redirect()
                  ->back()
                  ->withErrors(['Error ' . $e->getMessage()]);
          }
          
        } else {
          
          try {
              $payload = [
                  "date" => $request->date,
                  "reimbursement_department_id" => $request->reimbursement_department_id,
                  "nominal_pengajuan" => str_replace(".", "", $request->total_pengajuan),
                  "remark" => $request->remark_parent,
              ];

              $data = Reimbursement::find($id);

              $data->update($payload);
              ActivityLogger::log(
                  'reimbursement-driver',
                  'update',
                  'Reimbursement driver diperbaharui oleh approver',
                  $data->no_reimbursement,
                  'reimbursement',
                  $data->id,
                  ['status' => $data->status]
              );

              DB::select(DB::raw("UPDATE reimbursement_driver SET status=0  WHERE reimbursement_id = '$id'"));

              for ($i = 0; $i < count($request->toll); $i++) {
                  $oldDetailId = isset($request->id_detail[$i]) && ctype_digit((string) $request->id_detail[$i])
                      ? (int) $request->id_detail[$i]
                      : 0;
                  $legacyEvidence = '';
                  if ($oldDetailId > 0) {
                      $rowEv = DB::select(DB::raw("SELECT evidence FROM reimbursement_driver WHERE id='$oldDetailId'"));
                      $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
                  }

                  $new = new ReimbursementDriver();
                  $new->reimbursement_id = $data->id;
                  $new->toll = str_replace(".", "", $request->toll[$i]);
                  $new->gasoline = str_replace(".", "", $request->gasoline[$i]);
                  $new->parking = str_replace(".", "", $request->parking[$i]);
                  $new->others = str_replace(".", "", $request->others[$i]);
                  $new->subtotal = str_replace(".", "", $request->total[$i]);
                  $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                  $new->remark = $request->remark[$i];
                  $new->evidence = '';
                  $new->status = 1;
                  $new->save();

                  $allAttachmentNames = $this->syncDriverAttachments(
                      $request,
                      $i,
                      (int) $data->id,
                      (int) $new->id,
                      $oldDetailId,
                      $legacyEvidence
                  );
                  $new->evidence = $allAttachmentNames[0] ?? '';
                  $new->save();
              }

              $delete = DB::select(DB::raw("DELETE FROM reimbursement_driver WHERE reimbursement_id = '$id' AND status=0"));

              DB::commit();

              return redirect()
                  ->back()
                  ->with(['success' => 'Reimbursement Successfully Updated']);
              } catch (\Exception $e) {
                  // return var_dump($e);
                  dd($e->getMessage() . " at line " . $e->getLine());
                  DB::rollback();
                  return redirect()
                      ->back()
                      ->withErrors(['Error ' . $e->getMessage()]);
              } catch (\Throwable $e) {
                  // return var_dump($e);
                  dd($e->getMessage() . " at line " . $e->getLine());

                  DB::rollback();
                  return redirect()
                      ->back()
                      ->withErrors(['Error ' . $e->getMessage()]);
              }
        }
      
    }

    function approve(Request $requset, $id)
    {
        $data = Reimbursement::find($id);
        if (!$data) {
            return redirect()
                ->back()
                ->withErrors(['Reimbursement tidak ditemukan']);
        }

        $user = auth()->user();
        if ($data->status == 0 && $user->jabatan == "Direktur Operasional") {
            $data->update([
                'status' => 1,
                'mengetahui_op' => $user->name,
            ]);
        }
        if ($data->status == 1 && $user->jabatan == "Finance") {
            $data->update([
                'status' => 2,
                'mengetahui_finance' => $user->name,
            ]);
        }
        if ($data->status == 2 && $user->jabatan == "Owner") {
            $data->update([
                'status' => 3,
                'mengetahui_owner' => $user->name,
            ]);
        }
        ActivityLogger::log(
            'reimbursement-driver',
            'approve',
            'Reimbursement driver disetujui',
            $data->no_reimbursement,
            'reimbursement',
            $data->id,
            ['status' => $data->status]
        );
        return redirect()
            ->back()
            ->with(['success' => "Berhasil disetujui"]);
    }

    public function approveMultiple($id)
    {
        $idsArray = array_map('intval', explode(',', $id));
      	$user = auth()->user();
        $jab = $user->jabatan;

        $rows = Reimbursement::whereIn('id', $idsArray)->get();
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data'], 422);
        }
        if ($rows->pluck('status')->unique()->count() !== 1) {
            return response()->json(['message' => 'Pilih klaim dengan status yang sama.'], 422);
        }
        $bulkStatus = (int) $rows->first()->status;

        $canBulk = ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin'))
            || ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'Finance Supervisor' || $jab === 'superadmin'))
            || ($bulkStatus === 2 && ($jab === 'Owner' || $jab === 'Finance Supervisor' || $jab === 'superadmin'))
            || ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin'));
        if (!$canBulk) {
            return response()->json(['message' => 'Tidak dapat approve bulk untuk peran atau status ini.'], 422);
        }

      	if ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin')) {
            $status = 1;
            Reimbursement::whereIn('id', $idsArray)->where('status', 0)->update(['status' => $status, 'mengetahui_op' => $user->name]);
        } else if ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'Finance Supervisor' || $jab === 'superadmin')) {
            $status = 2;
            Reimbursement::whereIn('id', $idsArray)->where('status', 1)->update(['status' => $status, 'mengetahui_finance' => $user->name]);
        } else if ($bulkStatus === 2 && ($jab === 'Owner' || $jab === 'Finance Supervisor' || $jab === 'superadmin')) {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->where('status', 2)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        } else if ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin')) {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->where('status', 3)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        }
        ActivityLogger::log(
            'reimbursement-driver',
            'approve_multiple',
            'Reimbursement driver disetujui secara massal',
            null,
            'reimbursement',
            null,
            ['ids' => $idsArray, 'status' => $status]
        );
        
        
        // Ambil id_user dari tabel pengajuan
        $userIds = Reimbursement::whereIn('id', $idsArray)->pluck('id_user')->toArray();

        $reimbursement = Reimbursement::whereIn('id', $idsArray)->get(['id', 'id_user', 'no_reimbursement', 'nominal_pengajuan', 'created_by']);

        foreach ($reimbursement as $row) {
            // Ambil nomor HP user berdasarkan id_user
            $user = User::where('id', $row->id_user)->first(['phoneNumber']);

            if ($user && $user->phoneNumber) {
                if ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin')) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh *" .
                            auth()->user()->name  .
                            " (Head Department)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh HR GA.\n\nTerima kasih.
                               \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $row->id),
                    ])
                    ->post();

                    $hr_ga = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Finance'"));

                    foreach($hr_ga as $hr) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                            ->withData([
                                'target' => $hr->phoneNumber,
                                'message' =>
                                    "Hai *" .
                                    $hr->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."*  dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-driver/' . $row->id),
                            ])
                            ->post();
                    }
                } 

                if ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'Finance Supervisor' || $jab === 'superadmin')) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement nama *".$row->created_by."*  dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh *" .
                            auth()->user()->name .
                            " (HR GA)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance.\n\nTerima kasih.
                               \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $row->id),
                    ])
                    ->post();

                    $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

                    foreach($finance as $fn) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                            ->withData([
                                'target' => $fn->phoneNumber,
                                'message' =>
                                    "Hai *" .
                                    $fn->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."*  dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-driver/' . $row->id),
                            ])
                            ->post();

                    }
                } 

                if (($bulkStatus === 2 && ($jab === 'Owner' || $jab === 'Finance Supervisor' || $jab === 'superadmin')) || ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin'))) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui oleh *" .
                            auth()->user()->name .
                            " (Finance)*.\n\nSaat ini sedang menunggu Proses Pencairan oleh Finance.\n\nTerima kasih.
                    \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $row->id),
                    ])
                    ->post();

                    $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

                    foreach($finance as $fn) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                            ->withData([
                                'target' => $fn->phoneNumber,
                                'message' =>
                                    "Hai *" .
                                    $fn->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."*  dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-driver/' . $row->id),
                            ])
                            ->post();

                    }
                } 
            }
        }

        return response()->json(['message' => 'Status updated & WA sent']);

    }

    function print(Request $request)
    {
        if (isset($request->selected)) {
            
            $selected = $request->selected;
            $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id IN ($selected)"));
            $detail  = DB::select( DB::raw("SELECT no_reimbursement, date, reimbursement.created_at, reimbursement.remark, SUM(toll) as toll,sum(parking) as parking,sum(gasoline) as gasoline,sum(others) as others,sum(subtotal) as subtotal, GROUP_CONCAT(DISTINCT reimbursement_driver.payment_type ORDER BY reimbursement_driver.payment_type SEPARATOR ', ') as payment_type, mengetahui_op, mengetahui_finance, mengetahui_owner , reimbursement.no_reimbursement FROM reimbursement_driver LEFT JOIN reimbursement ON reimbursement.id = reimbursement_driver.reimbursement_id WHERE reimbursement_id IN ($selected) GROUP BY reimbursement.id"));
            $user = User::find($request->driver == 'null' || $request->driver == "" || $request->driver == null ? auth()->user()->id : $request->driver);
            $total_toll = DB::select( DB::raw("SELECT sum(toll) AS total FROM reimbursement_driver WHERE reimbursement_id IN ($selected)"))['0']->total;
            $total_parking = DB::select( DB::raw("SELECT sum(parking) AS total FROM reimbursement_driver WHERE reimbursement_id IN ($selected)"))['0']->total;
            $total_gasoline = DB::select( DB::raw("SELECT sum(gasoline) AS total FROM reimbursement_driver WHERE reimbursement_id IN ($selected)"))['0']->total;
            $total_others = DB::select( DB::raw("SELECT sum(others) AS total FROM reimbursement_driver WHERE reimbursement_id IN ($selected)"))['0']->total;
            $total = DB::select( DB::raw("SELECT sum(subtotal) AS total FROM reimbursement_driver WHERE reimbursement_id IN ($selected)"))['0']->total;
            

            return view('print.driver-reimbursement-checkbox', [
                'data' => $data,
                'detail' => $detail,
                'start_date' => $request->start,
                'end_date' => $request->end,
                'user' => $user,
                'total_toll' => $total_toll,
                'total_parking' => $total_parking,
                'total_gasoline' => $total_gasoline,
                'total_others' => $total_others,
                'total' => $total,
            ]);
            

        } else {

            $data = ReimbursementDriver::selectRaw("SUM(toll) as toll,sum(parking) as parking,sum(gasoline) as gasoline,sum(others) as others,sum(subtotal) as total, GROUP_CONCAT(DISTINCT reimbursement_driver.payment_type ORDER BY reimbursement_driver.payment_type SEPARATOR ', ') as payment_type, date, reimbursement.remark, name, vehicleNo, mengetahui_op, mengetahui_finance, mengetahui_owner , reimbursement.no_reimbursement, reimbursement.created_at")->join('reimbursement', 'reimbursement.id', '=', 'reimbursement_driver.reimbursement_id')->join('users', 'users.id', '=', 'reimbursement.id_user');
            
            $targetUserId = ($request->driver == 'null' || $request->driver == "" || $request->driver == null)
                ? auth()->user()->id
                : $request->driver;
            $targetUser = User::find($targetUserId);
            $head_dept = $targetUser && !empty($targetUser->nama_approval)
                ? $targetUser->nama_approval
                : '-';
            

            if (isset($request->start)) {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->start);
            }

            if (isset($request->end)) {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->end);
            }

            if (isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status', $request->status);
            }

            if (isset($request->driver) && $request->driver != "" && $request->driver != "null") {
                $data = $data->where('reimbursement.id_user', '=', $request->driver);
            }

            if (isset($request->payment_type) && $request->payment_type != "" && $request->payment_type != "ALL") {
                $data = $data->where('reimbursement_driver.payment_type', $request->payment_type);
            }

            if (auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            $user = User::find($request->driver == 'null' || $request->driver == "" || $request->driver == null ? auth()->user()->id : $request->driver);
            
            
            if ($data->count()==0) {
                echo "Data not found. Please make sure the <strong>search button has been clicked first</strong>.";
            } else {
                return view('print.driver-reimbursement', [
                    'start_date' => $request->start,
                    'end_date' => $request->end,
                    'data' => $data->groupBy('reimbursement.id')->get(),
                    // 'obj' => Reimbursement::,
                    'user' => $user,
                    'head_dept' => $head_dept,
                ]);
            }
            
        }
        
    }
}
