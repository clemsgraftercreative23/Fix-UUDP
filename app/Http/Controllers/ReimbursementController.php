<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\ReimbursementDetail;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
use App\User;
class ReimbursementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan');

            if (auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            if (!empty($request->first) && !empty($request->last)) {
                $first = $request->first;
                $last = $request->last;
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('reimbursement.created_at', [$from, $to]);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()
                ->of($data)
                ->addColumn('action', function ($data) {
                    if ($data->status == 0) {
                        $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                    } elseif ($data->status == 1) {
                        $button = '<button  class="view btn btn-success btn-sm">APPROVED Direktur Operasional</button>';
                    } elseif ($data->status == 2) {
                        $button = '<button   class="view btn btn-success btn-sm">APPROVED Financec</button>';
                    } elseif ($data->status == 3) {
                        $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                    } elseif ($data->status == 4) {
                        $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                    } elseif ($data->status == 5) {
                        $button = '<button  class="view btn btn-success btn-sm">DICAIRKAN</button>';
                    }
                    $button .= '&nbsp;&nbsp;';

                    return $button;
                })
                ->editColumn('no_project', function ($data) {
                    if ($data->id_project == null) {
                        return $data->remark;
                    }
                    return $data->no_project;
                })
                ->addColumn('nominal_pengajuan', function ($data) {
                    $button = '';
                    $button .= number_format($data->nominal_pengajuan, 0, ',', '.');
                    return $button;
                })
                ->editColumn('no_reimbursement', function ($data) {
                    return "<a href='" . route('reimbursement.show', $data->id) . "'>" . $data->no_reimbursement . "</a>";
                })
                ->rawColumns(['action', 'nominal_pengajuan', 'no_reimbursement'])
                ->make(true);
        }

        return view('reimbursement.index', [
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = [
                "id_user" => auth()->user()->id,
                "no_reimbursement" => "UUPD-REIMBURSE-00" . (Reimbursement::count() + 1),
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => str_replace('.', '', $request->total_pengajuan),
                "status" => 0,
                "created_by" => auth()->user()->name,
                "id_project" => $request->id_project,
                "remark" => $request->keterangan_project,
            ];

            $data = Reimbursement::create($data);
            for ($i = 0; $i < count($request->id_kelompok); $i++) {
                $payload = [
                    'id_reimbursement' => $data->id,
                    'id_kelompok' => $request->id_kelompok[$i],
                    'note_kelompok' => $request->plain_kelompok[$i],
                    'catatan' => $request->keterangan_pengajuan[$i],
                    'nominal_pengajuan' => str_replace('.', '', $request->nominal_pengajuan[$i]),
                    'status_pencairan' => 0,
                ];

                if (isset($request->proof[$i])) {
                    $image = $request->file('proof')[$i];
                    $new_name = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $new_name);
                    $payload['file'] = $new_name;
                }

                if (isset($request->file[$i])) {
                    $image = $request->file('file')[$i];
                    $new_name = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $new_name);
                    $payload['file'] = $new_name;
                }

                $dt = ReimbursementDetail::create($payload);
            }

            DB::commit();
            return redirect()
                ->back()
                ->with(['success' => 'Reimbursement Berhasil Diajukan']);
        } catch (\Exception $e) {
            dd($e->getMessage() . " at line " . $e->getLine());
            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            dd($e->getMessage() . " at line " . $e->getLine());

            DB::rollback();
            return redirect()
                ->back()
                ->withErrors(['Error ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $data = Reimbursement::find($id);

        return view('reimbursement.detail', [
            'data' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    function approve(Request $requset, $id)
    {
        $data = Reimbursement::find($id);
        
        $cek_type  = DB::select( DB::raw("SELECT reimbursement_type FROM reimbursement WHERE id='$id'"))['0']->reimbursement_type;
        
        if($cek_type==1) {
            $direct = "/reimbursement-driver/";
        } else if($cek_type==2) {
            $direct = "/reimbursement-travel/";
        } else {
            $direct = "/reimbursement-entertaiment/";
        }
        
        if (!$data) {
            return redirect()
                ->back()
                ->withErrors(['Reimbursement tidak ditemukan']);
        }

        $approver = auth()->user();
        $nama_approval = ucfirst($approver->name);
        if ($approver->jabatan == 'Direktur Operasional' || ($approver->jabatan == 'superadmin' && (int) $data->status === 0)) {
            $level = 'Head Department';
        } else if ($approver->jabatan == 'Finance' || ($approver->jabatan == 'superadmin' && (int) $data->status === 1)) {
            $level = 'HR GA';
        } else if ($approver->jabatan == 'superadmin' && (int) $data->status === 2) {
            $level = 'Finance';
        } else {
            $level = 'Finance';
        }

        $processed = false;
        if ($data->status == 0 && ($approver->jabatan == 'Direktur Operasional' || $approver->jabatan == 'superadmin')) {
            $processed = true;
            $data->update([
                'status' => 1,
                'mengetahui_op' => $approver->name,
            ]);

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
                        "* telah diterima oleh *" .
                        $nama_approval .
                        " (".$level.")* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh HR GA.\n\nTerima kasih.
                           \n\nKlik untuk melihat detail pengajuan : " .
                        url(''.$direct.'' . $data->id),
                ])
                ->post();

            $hr_ga = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Finance'"));

            foreach($hr_ga as $row) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $row->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->name .
                            "*,\n\nPengajuan reimbursement dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url(''.$direct.'' . $data->id),
                    ])
                    ->post();

            }

            
        } elseif ($data->status == 1 && ($approver->jabatan == 'Finance' || $approver->jabatan == 'superadmin')) {
            $processed = true;
            $data->update([
                'status' => 2,
                'mengetahui_finance' => $approver->name,
            ]);

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
                        "* telah diterima oleh *" .
                        $nama_approval .
                        " (".$level.")* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance.\n\nTerima kasih.
                           \n\nKlik untuk melihat detail pengajuan : " .
                        url(''.$direct.'' . $data->id),
                ])
                ->post();

            $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

            foreach($finance as $row) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $row->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->name .
                            "*,\n\nPengajuan reimbursement dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url(''.$direct.'' . $data->id),
                    ])
                    ->post();

            }
        } elseif ($data->status == 2 && ($approver->jabatan == 'Owner' || $approver->jabatan == 'superadmin')) {
            $processed = true;
            $data->update([
                'status' => 3,
                'mengetahui_owner' => $approver->name,
            ]);

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
                        "* telah disetujui oleh *" .
                        $nama_approval .
                        " (".$level.")*.\n\nSaat ini sedang menunggu Proses Pencairan oleh Finance.\n\nTerima kasih.
                \n\nKlik untuk melihat detail pengajuan : " .
                        url(''.$direct.'' . $data->id),
                ])
                ->post();

            $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

            foreach($finance as $row) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $row->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $row->name .
                            "*,\n\nPengajuan reimbursement dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url(''.$direct.'' . $data->id),
                    ])
                    ->post();

            }

            
        }

        if ($processed) {
            return redirect()
                ->back()
                ->with(['success' => "Berhasil disetujui"]);
        }

        return redirect()
            ->back()
            ->withErrors(['Tidak dapat memproses approval: periksa peran Anda dan status klaim.']);
    }

    function reject(Request $request, $id)
    {        
        
        $data = Reimbursement::find($id);
        $cek_type  = DB::select( DB::raw("SELECT reimbursement_type FROM reimbursement WHERE id='$id'"))['0']->reimbursement_type;
        
        if($cek_type==1) {
            $direct = "/reimbursement-driver/";
        } else if($cek_type==2) {
            $direct = "/reimbursement-travel/";
        } else {
            $direct = "/reimbursement-entertaiment/";
        }

        if (!$data) {
            return redirect()
                ->back()
                ->withErrors(['Reimbursement tidak ditemukan']);
        }

        $user = auth()->user();
        

        $data->update([
            'status' => 9,
            'reject_reason' => $request->reason,
            'reject_by' => $user->id,
        ]);

        $nama_penolak = ucfirst(auth()->user()->name);
        if (auth()->user()->jabatan=='Direktur Operasional') {
            $level = 'Head Department';
        } else if (auth()->user()->jabatan=='Finance') {
            $level = 'HR GA';
        } else {
            $level = 'Finance';
        }

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
                    "* telah ditolak oleh *" .
                    $nama_penolak .
                    " (".$level.")* .\n\nSilahkan cek kembali pengajuan Anda untuk lebih detail.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : " .
                    url(''.$direct.'' . $data->id),
            ])
            ->post();

       
        return redirect()
            ->back()
            ->with(['success' => "Reimbursement Rejected"]);
    }

    function listUser(Request $request)
    {
        $status = $request->status;
        $type = $request->reimbursement_type;

        if ($status == 'ALL') {
            $user = DB::select(DB::raw("SELECT users.id, users.name FROM reimbursement LEFT JOIN users ON users.id = reimbursement.id_user WHERE reimbursement_type='$type' GROUP BY users.id"));
        } else {
            $user = DB::select(DB::raw("SELECT users.id, users.name FROM reimbursement LEFT JOIN users ON users.id = reimbursement.id_user WHERE reimbursement_type='$type' AND status='$status' GROUP BY users.id"));
        }

        return json_encode($user);
    }

    function listSettlement(Request $request)
    {
        $status = $request->status;
        $type = $request->reimbursement_type;

        $user = DB::table('reimbursement')
            ->leftJoin('users', 'users.id', '=', 'reimbursement.id_user')
            ->select('users.id', 'users.name')
            ->whereIn('reimbursement.status', [3, 5]);

        if (!empty($status) && $status !== 'ALL') {
            $user->where('reimbursement.status', $status);
        }

        if (!empty($type)) {
            $user->where('reimbursement.reimbursement_type', $type);
        }

        $user = $user->groupBy('users.id', 'users.name')->get();
        
        return json_encode($user);
    }
}
