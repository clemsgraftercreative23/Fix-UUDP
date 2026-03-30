<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\User;
use App\ReimbursementDetail;
use App\ReimbursementEntertaiment;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
class EntertaimentReimbursementController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id_user = auth()->user()->id;
        
        if(request()->ajax())
        {

            if(auth()->user()->jabatan=='superadmin') {
                $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3)->where('reimbursement.id_user', $id_user);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3)->where('reimbursement.status',$request->status)->where('reimbursement.id_user', $id_user);    
                }
                
            }
            

            if(isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at','>=',$request->first);
            }

            if(isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at','<=',$request->last);
            }

            if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
            }
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }


            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()->of($data)
            ->addColumn('action', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                }elseif ($data->status == 1) {
                $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">PROCESS SETTLEMET</button>';
                } elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                } elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                } elseif ($data->status == 9){
                    if($data->mengetahui_op=='-') {
                        $meng = 'HEAD DEPT';
                    } else if($data->mengetahui_finance=='-') {
                        $meng = 'HR GA';
                    } else if($data->mengetahui_owner=='-') {
                        $meng = 'FINANCE';
                    }
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED '.$meng.'</button>';
                } elseif ($data->status == 10){
                    $button = '<button  class="view btn btn-warning btn-sm">DRAFT</button>';
                }
                $button .= '&nbsp;&nbsp;';

                return $button;

            })
            ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
            })
            ->editColumn('no_project', function ($data) {
               
                return $data->user->name;
            })
            ->addColumn('nominal_pengajuan', function ($data) {
                $button ='';
                $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                return $button;
            })
            ->editColumn('no_reimbursement', function ($data) {
                return "<a href='".route('reimbursement-entertaiment.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action', 'checkbox' ,'nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }
        
        $check_approval  = DB::select( DB::raw("SELECT count(id) AS id FROM users WHERE id_approval = '$id_user'"))['0']->id;

        return view('reimbursement-entertaiment.index',[
            'check_approval' => $check_approval,
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
        ]);
    }
    
    
    public function approval(Request $request)
    {
        $id_user = auth()->user()->id;
        
        if(request()->ajax())
        {

            if(auth()->user()->jabatan=='Finance' || auth()->user()->jabatan=='Owner' || auth()->user()->jabatan=='superadmin') {
                $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3)->where('reimbursement.status', '!=',10);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3)->where('users.id_approval', $id_user)->where('reimbursement.status', '!=',10);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',3)->where('reimbursement.status',$request->status)->where('users.id_approval', $id_user)->where('reimbursement.status', '!=',10);    
                }
                
            }
            

            if(isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at','>=',$request->first);
            }

            if(isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at','<=',$request->last);
            }

            if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
            }
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }


            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()->of($data)
            ->addColumn('action', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                }elseif ($data->status == 1) {
                $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">PROCESS SETTLEMET</button>';
                } elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                } elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                } elseif ($data->status == 9){
                    if($data->mengetahui_op=='-') {
                        $meng = 'HEAD DEPT';
                    } else if($data->mengetahui_finance=='-') {
                        $meng = 'HR GA';
                    } else if($data->mengetahui_owner=='-') {
                        $meng = 'FINANCE';
                    }
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED '.$meng.'</button>';
                }
                $button .= '&nbsp;&nbsp;';

                return $button;

            })
            ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
            })
            ->editColumn('no_project', function ($data) {
               
                return $data->user->name;
            })
            ->addColumn('nominal_pengajuan', function ($data) {
                $button ='';
                $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                return $button;
            })
            ->editColumn('no_reimbursement', function ($data) {
                return "<a href='".route('reimbursement-entertaiment.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action', 'checkbox' ,'nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }

        return view('reimbursement-entertaiment.approval',[
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
        ]);
    }

    
    public function store(Request $request)
    {
        DB::beginTransaction();
        $id_max  = DB::select( DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id + 1;
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
                "no_reimbursement" => "UUDP-REIMBURSE-E-00".$id_max,
                "date" => $request->date,
                "reimbursement_department_id" => $request->reimbursement_department_id,
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => str_replace(".",'',$request->total_pengajuan),
                "status" => $status,
                "reimbursement_type" => 3,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark_parent,
            ];
    
            $data = Reimbursement::create($data);
            $id_reim  = DB::select( DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id;

            for ($i = 0; $i < count($request->empty_zone); $i++) {
                if (empty($request->proof[$i]) && !empty($request->file[$i])) {
                    $image = $request->file[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                } elseif (empty($request->file[$i]) && !empty($request->proof[$i])) {
                    $image = $request->proof[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                }

                $new = new ReimbursementEntertaiment();
                $new->reimbursement_id = $id_reim;
                $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                $new->empty_zone = str_replace(".", "", $request->empty_zone[$i]);
                $new->attendance = str_replace(".", "", $request->attendance[$i]);
                $new->position = str_replace(".", "", $request->position[$i]);
                $new->place = str_replace(".", "", $request->place[$i]);
                $new->guest = str_replace(".", "", $request->guest[$i]);
                $new->guest_position = str_replace(".", "", $request->guest_position[$i]);
                $new->company = str_replace(".", "", $request->company[$i]);
                $new->type = str_replace(".", "", $request->type[$i]);
                $new->amount = str_replace(".", "", $request->amount[$i]);
                $new->remark = $request->remark[$i];
                $new->evidence = $evidence;
                $new->status = 1;
                $new->save();
            }

            $id_main = DB::select( DB::raw("SELECT max(id) as id_main FROM reimbursement"))['0']->id_main;
            $total_bdc  = DB::select( DB::raw("SELECT sum(amount) AS total FROM reimbursement_entertaiments WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $total_cash  = DB::select( DB::raw("SELECT sum(amount) AS total FROM reimbursement_entertaiments WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);

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
                            url('/reimbursement-entertaiment/' . $data->id),
                    ])->post();
                
                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));


                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $approval['0']->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $approval['0']->name .
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-entertaiment/' . $data->id),
                    ])->post();
            }

            DB::commit();
            return redirect()->back()->with(['success' => $notif]);

        } catch(\Exception $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
    
        } catch(\Throwable $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());

            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
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
        $data = Reimbursement::find($id);
        $detail = DB::select(DB::raw("SELECT * FROM reimbursement_entertaiments WHERE reimbursement_id='$id'"));
        $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, metode_cash FROM reimbursement WHERE id = '$id'"));

        $bdc = $cek['0']->total_bdc;
        $cash = $cek['0']->total_cash;
        $metode_cash_ = $cek['0']->metode_cash;
        if ($metode_cash_ == null) {
            $metode_cash = "";
        } else {
            $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
        }

        return view('reimbursement-entertaiment.detail',[
            'data' => $data,
            'detail' => $detail,
            'bdc' => $bdc,
            'cash' => $cash,
            'metode_cash' => $metode_cash,
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
        DB::beginTransaction();
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; 
            $notif = 'Reimbursement Successfully Saved as Draft';
        } 
        try {
            $payload = [
                "id_user" => auth()->user()->id,
                // "no_reimbursement" => "UUDP-REIMBURSE-E-00".(Reimbursement::count()+1),
                "date" => $request->date,
                "reimbursement_department_id" => $request->reimbursement_department_id,
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => str_replace(".","",$request->total_pengajuan),
                "status" => $status,
                "reimbursement_type" => 3,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark_parent,
            ];

            $data = Reimbursement::find($id);
    
            $data->update($payload);
            
            
            DB::select(DB::raw("UPDATE reimbursement_entertaiments SET status=0  WHERE reimbursement_id = '$id'"));
            
            for ($i=0; $i < count($request->empty_zone); $i++) { 
                
                if (empty($request->proof[$i]) && empty($request->file[$i])) {
                    $id_detail = $request->id_detail[$i];
                    $evidence = DB::select(DB::raw("SELECT evidence FROM reimbursement_entertaiments WHERE id='$id_detail'"))['0']->evidence;
                } elseif (empty($request->proof[$i]) && !empty($request->file[$i])) {
                    $image = $request->file[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                } elseif (empty($request->file[$i]) && !empty($request->proof[$i])) {
                    $image = $request->proof[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                }
                
                $new = new ReimbursementEntertaiment();
                $new->reimbursement_id = $data->id;
                $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                $new->empty_zone = str_replace(".", "", $request->empty_zone[$i]);
                $new->attendance = str_replace(".", "", $request->attendance[$i]);
                $new->position = str_replace(".", "", $request->position[$i]);
                $new->place = str_replace(".", "", $request->place[$i]);
                $new->guest = str_replace(".", "", $request->guest[$i]);
                $new->guest_position = str_replace(".", "", $request->guest_position[$i]);
                $new->company = str_replace(".", "", $request->company[$i]);
                $new->type = str_replace(".", "", $request->type[$i]);
                $new->amount = str_replace(".", "", $request->amount[$i]);
                $new->remark = str_replace(".", "", $request->remark[$i]);
                $new->evidence = $evidence;
                $new->status = 1;
                $new->save();
                
            }
            
            $delete = DB::select(DB::raw("DELETE FROM reimbursement_entertaiments WHERE reimbursement_id = '$id' AND status=0"));
            
            DB::commit();

            if (isset($_POST['save'])) {

                $data = Reimbursement::find($id);
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
                            url('/reimbursement-entertaiment/' . $data->id),
                    ])->post();

                $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                        $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                        })->get();

                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));


                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: G-BJE9txd#aXDewvme7u'])
                    ->withData([
                        'target' => $approval['0']->phoneNumber,
                        'message' =>
                            "Hai *" .
                            $approval['0']->name .
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-entertaiment/' . $data->id),
                    ])->post();
            }
            
            return redirect()->back()->with(['success' => $notif]);

        } catch(\Exception $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
    
        } catch(\Throwable $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());

            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
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
                    "nominal_pengajuan" => str_replace(".","",$request->total_pengajuan),
                    "remark" => $request->remark_parent,
                    "status" => $status,
                ];

                $data = Reimbursement::find($id);
        
                $data->update($payload);
                
                
                DB::select(DB::raw("UPDATE reimbursement_entertaiments SET status=0  WHERE reimbursement_id = '$id'"));
                
                for ($i=0; $i < count($request->empty_zone); $i++) { 
                    
                    if (empty($request->proof[$i]) && empty($request->file[$i])) {
                        $id_detail = $request->id_detail[$i];
                        $evidence = DB::select(DB::raw("SELECT evidence FROM reimbursement_entertaiments WHERE id='$id_detail'"))['0']->evidence;
                    } elseif (empty($request->proof[$i]) && !empty($request->file[$i])) {
                        $image = $request->file[$i];
                        $evidence = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $evidence);
                    } elseif (empty($request->file[$i]) && !empty($request->proof[$i])) {
                        $image = $request->proof[$i];
                        $evidence = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $evidence);
                    }
                    
                    $new = new ReimbursementEntertaiment();
                    $new->reimbursement_id = $data->id;
                    $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                    $new->empty_zone = str_replace(".", "", $request->empty_zone[$i]);
                    $new->attendance = str_replace(".", "", $request->attendance[$i]);
                    $new->position = str_replace(".", "", $request->position[$i]);
                    $new->place = str_replace(".", "", $request->place[$i]);
                    $new->guest = str_replace(".", "", $request->guest[$i]);
                    $new->guest_position = str_replace(".", "", $request->guest_position[$i]);
                    $new->company = str_replace(".", "", $request->company[$i]);
                    $new->type = str_replace(".", "", $request->type[$i]);
                    $new->amount = str_replace(".", "", $request->amount[$i]);
                    $new->remark = str_replace(".", "", $request->remark[$i]);
                    $new->evidence = $evidence;
                    $new->status = 1;
                    $new->save();
                    
                }
                
                $delete = DB::select(DB::raw("DELETE FROM reimbursement_entertaiments WHERE reimbursement_id = '$id' AND status=0"));
                
                DB::commit();

                return redirect()->back()->with(['success' => $notif]);

            } catch(\Exception $e) {
                // return var_dump($e);
                dd($e->getMessage() . " at line ". $e->getLine());
                DB::rollback();
                return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
        
            } catch(\Throwable $e) {
                // return var_dump($e);
                dd($e->getMessage() . " at line ". $e->getLine());

                DB::rollback();
                return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
            }

        } else {

            try {
                $payload = [
                    "date" => $request->date,
                    "reimbursement_department_id" => $request->reimbursement_department_id,
                    "nominal_pengajuan" => str_replace(".","",$request->total_pengajuan),
                    "remark" => $request->remark_parent
                ];

                $data = Reimbursement::find($id);
        
                $data->update($payload);
                
                
                DB::select(DB::raw("UPDATE reimbursement_entertaiments SET status=0  WHERE reimbursement_id = '$id'"));
                
                for ($i=0; $i < count($request->empty_zone); $i++) { 
                    
                    if (empty($request->proof[$i]) && empty($request->file[$i])) {
                        $id_detail = $request->id_detail[$i];
                        $evidence = DB::select(DB::raw("SELECT evidence FROM reimbursement_entertaiments WHERE id='$id_detail'"))['0']->evidence;
                    } elseif (empty($request->proof[$i]) && !empty($request->file[$i])) {
                        $image = $request->file[$i];
                        $evidence = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $evidence);
                    } elseif (empty($request->file[$i]) && !empty($request->proof[$i])) {
                        $image = $request->proof[$i];
                        $evidence = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $evidence);
                    }
                    
                    $new = new ReimbursementEntertaiment();
                    $new->reimbursement_id = $data->id;
                    $new->payment_type = str_replace(".", "", $request->payment_type[$i]);
                    $new->empty_zone = str_replace(".", "", $request->empty_zone[$i]);
                    $new->attendance = str_replace(".", "", $request->attendance[$i]);
                    $new->position = str_replace(".", "", $request->position[$i]);
                    $new->place = str_replace(".", "", $request->place[$i]);
                    $new->guest = str_replace(".", "", $request->guest[$i]);
                    $new->guest_position = str_replace(".", "", $request->guest_position[$i]);
                    $new->company = str_replace(".", "", $request->company[$i]);
                    $new->type = str_replace(".", "", $request->type[$i]);
                    $new->amount = str_replace(".", "", $request->amount[$i]);
                    $new->remark = str_replace(".", "", $request->remark[$i]);
                    $new->evidence = $evidence;
                    $new->status = 1;
                    $new->save();
                    
                }
                
                $delete = DB::select(DB::raw("DELETE FROM reimbursement_entertaiments WHERE reimbursement_id = '$id' AND status=0"));
                
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

    public function destroy($id)
    {
        //
    }
    
    function approve(Request $requset, $id) {
        $data = Reimbursement::find($id);
        if(!$data)
            return redirect()->back()->withErrors(['Reimbursement tidak ditemukan']);

        $user = auth()->user();
        if($data->status == 0 && $user->jabatan == "Direktur Operasional") {
            $data->update([
                'status' => 1,
                'mengetahui_op' => $user->name
            ]);
        }
        if($data->status == 1 && $user->jabatan == "Finance") {
            $data->update([
                'status' => 2,
                'mengetahui_finance' => $user->name
            ]);
        }
        if($data->status == 2 && $user->jabatan == "Owner") {
            $data->update([
                'status' => 3,
                'mengetahui_owner' => $user->name
            ]);

        }
        return redirect()->back()->with(['success' => "Berhasil disetujui"]);
    }
    
    function print(Request $request) {

        $selectRaw = 'reimbursement.id AS id_main, reimbursement.nominal_pengajuan, reimbursement.no_reimbursement, '
            . 'reimbursement.mengetahui_op, reimbursement.mengetahui_finance, reimbursement.mengetahui_owner, '
            . 'reimbursement.date, reimbursement.remark AS remark_header, reimbursement.status AS reimbursement_status, '
            . 'reimbursement.reimbursement_department_id, users.name, users.idKaryawan AS nik, '
            . 'departemen.nama_departemen, reimbursement.created_at';

        if ($request->filled('selected')) {

            $selected = explode(',', $request->selected);

            $data = Reimbursement::selectRaw($selectRaw)
                ->join('users', 'users.id', '=', 'reimbursement.id_user')
                ->leftJoin('departemen', 'departemen.id', '=', 'reimbursement.reimbursement_department_id')
                ->whereIn('reimbursement.id', $selected)
                ->where('reimbursement.reimbursement_type', 3);

            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total')->whereIn('reimbursement.id', $selected)
                ->where('reimbursement_type', 3);
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total')->whereIn('reimbursement.id', $selected)
                ->where('reimbursement_type', 3);

            if ($request->filled('start')) {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->start);
                $bdc = $bdc->whereDate('reimbursement.created_at', '>=', $request->start);
                $total_cash = $total_cash->whereDate('reimbursement.created_at', '>=', $request->start);
            }

            if ($request->filled('end')) {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->end);
                $bdc = $bdc->whereDate('reimbursement.created_at', '<=', $request->end);
                $total_cash = $total_cash->whereDate('reimbursement.created_at', '<=', $request->end);
            }

            if ($request->filled('status') && $request->status !== 'ALL') {
                $data = $data->where('reimbursement.status', $request->status);
                $bdc = $bdc->where('reimbursement.status', $request->status);
                $total_cash = $total_cash->where('reimbursement.status', $request->status);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC')->get();

            $head_dept = $data->first() ? $data->first()->mengetahui_op : '-';

            $bdc = optional($bdc->first())->total ?? 0;
            $total_cash = optional($total_cash->first())->total ?? 0;

        } else {

            $data = Reimbursement::selectRaw($selectRaw)
                ->join('users', 'users.id', '=', 'reimbursement.id_user')
                ->leftJoin('departemen', 'departemen.id', '=', 'reimbursement.reimbursement_department_id')
                ->where('reimbursement.reimbursement_type', 3);

            $id_user = $request->input('driver', auth()->user()->id);
            $head_dept_row = DB::table('users')->where('id', $id_user)->value('nama_approval');
            $head_dept = $head_dept_row ?? '-';

            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total')->where('reimbursement_type', 3);
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total')->where('reimbursement_type', 3);

            if ($request->filled('start')) {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->start);
                $bdc = $bdc->whereDate('reimbursement.created_at', '>=', $request->start);
                $total_cash = $total_cash->whereDate('reimbursement.created_at', '>=', $request->start);
            }

            if ($request->filled('end')) {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->end);
                $bdc = $bdc->whereDate('reimbursement.created_at', '<=', $request->end);
                $total_cash = $total_cash->whereDate('reimbursement.created_at', '<=', $request->end);
            }

            if ($request->filled('status') && $request->status !== 'ALL') {
                $data = $data->where('reimbursement.status', $request->status);
                $bdc = $bdc->where('reimbursement.status', $request->status);
                $total_cash = $total_cash->where('reimbursement.status', $request->status);
            }

            if ($request->filled('driver')) {
                $data = $data->where('reimbursement.id_user', '=', $request->driver);
                $bdc = $bdc->where('reimbursement.id_user', '=', $request->driver);
                $total_cash = $total_cash->where('reimbursement.id_user', '=', $request->driver);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC')->get();

            $bdc = optional($bdc->first())->total ?? 0;
            $total_cash = optional($total_cash->first())->total ?? 0;
        }

        $reimbursementIds = $data->pluck('id_main')->unique()->filter()->values()->all();
        $detail = collect();
        if (count($reimbursementIds) > 0) {
            $detail = ReimbursementEntertaiment::whereIn('reimbursement_id', $reimbursementIds)
                ->where('status', 1)
                ->orderBy('id')
                ->get();
        }

        if ($data->count() == 0) {
            echo "Data not found. Please make sure the <strong>search button has been clicked first</strong>.";
        } else {
            return view('print.entertainment-reimbursement', compact('data', 'detail', 'bdc', 'total_cash', 'head_dept'));
        }

    }

    public function approveMultiple($id)
    {
        //if (auth()->user()->jabatan=='Direktur Operasional') {
        //    $status = 1;
        //} else if (auth()->user()->jabatan=='Finance') {
        //    $status = 2;
        //} else {
        //    $status = 3;
        //}
        //$idsArray = array_map('intval', explode(',', $id));
        //Reimbursement::whereIn('id', $idsArray)->update(['status' => $status]);
      
        $idsArray = array_map('intval', explode(',', $id));
      	$user = auth()->user();
      
      	if (auth()->user()->jabatan=='Direktur Operasional') {
            $status = 1;
            Reimbursement::whereIn('id', $idsArray)->update(['status' => $status, 'mengetahui_op' => $user->name]);
        } else if (auth()->user()->jabatan=='Finance') {
            $status = 2;
            Reimbursement::whereIn('id', $idsArray)->update(['status' => $status, 'mengetahui_finance' => $user->name]);
        } else {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        }
        
        // Ambil id_user dari tabel pengajuan
        $userIds = Reimbursement::whereIn('id', $idsArray)->pluck('id_user')->toArray();

        $reimbursement = Reimbursement::whereIn('id', $idsArray)->get(['id', 'id_user', 'no_reimbursement', 'nominal_pengajuan', 'created_by']);

        foreach ($reimbursement as $row) {
            // Ambil nomor HP user berdasarkan id_user
            $user = User::where('id', $row->id_user)->first(['phoneNumber']);

            if ($user && $user->phoneNumber) {
                if (auth()->user()->jabatan=='Direktur Operasional') {
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
                            url('/reimbursement-entertaiment/' . $row->id),
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
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-entertaiment/' . $row->id),
                            ])
                            ->post();
                    }
                } 

                if (auth()->user()->jabatan=='Finance') {
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
                            auth()->user()->name .
                            " (HR GA)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance.\n\nTerima kasih.
                               \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-entertaiment/' . $row->id),
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
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-entertaiment/' . $row->id),
                            ])
                            ->post();

                    }
                } 

                if (auth()->user()->jabatan=='Owner') {
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
                            url('/reimbursement-entertaiment/' . $row->id),
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
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-entertaiment/' . $row->id),
                            ])
                            ->post();

                    }
                } 
            }
        }

        return response()->json(['message' => 'Status updated & WA sent']);

    }
}
