<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\User;
use App\ReimbursementDetail;
use App\ReimbursementMedical;
use App\ReimbursementMedicalExpense;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
use App\Support\ActivityLogger;
class MedicalReimbursementController extends Controller
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
        if(request()->ajax())
        {

            $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',4);

            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            if(isset($request->first) && $request->first != "") {
                $data = $data->where('reimbursement.date','>=',$request->first);
            }

            if(isset($request->last) && $request->last != "") {
                $data = $data->where('reimbursement.date','<=',$request->last);
            }

            if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
            }

            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()->of($data)
            ->addColumn('action', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                }elseif ($data->status == 1) {
                $button = '<button  class="view btn btn-success btn-sm">APPROVED Direktur Operasional</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED Financec</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                } elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                }elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">DICAIRKAN</button>';
                }
                $button .= '&nbsp;&nbsp;';

                return $button;

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
                return "<a href='".route('reimbursement-medical.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action','nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }

        return view('reimbursement-medical.index',[
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
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
                "no_reimbursement" => "UUPD-REIMBURSE-E-00".(Reimbursement::where('no_reimbursement','like',"%-E-%")->count()+1),
                "date" => $request->date,
                "reimbursement_department_id" => $request->reimbursement_department_id,
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => str_replace(".",'',$request->total_pengajuan),
                "status" => 0,
                "reimbursement_type" => 4,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark_parent,
            ];
            if(isset($request->proof)) {
                $image = $request->file('proof');
                $new_name = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $new_name);
                $data['file'] = $new_name;
            }

            
            if(isset($request->file)) {
                $image = $request->file('file');
                $new_name = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $new_name);
                $data['file'] = $new_name;
            }
    
            $data = Reimbursement::create($data);
            ActivityLogger::log(
                'reimbursement-medical',
                'create',
                'Reimbursement medical dibuat',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => 0]
            );
            $datas = [];
            foreach ($request->details as $key => $value) {
                if(($value) != null) {
                    $payload = $value;
                    $payload['reimbursement_id'] = $data->id;
                    $payload['patient_name'] = $request->patient_name;
                    $payload['diagnose'] = $request->diagnose;
                    $payload['status'] = $request->status_employee;
                    // unset()
                    $datas[] = $payload;
                    // dd($payload);
                    $dt = ReimbursementMedical::create($payload);
                }
            }
            
            foreach ($request->expenses as $key => $value) {
                if(($value) != null) {
                    $payload = $value;
                    $payload['reimbursement_id'] = $data->id;
                    $payload['amount'] = str_replace(".",'',$value['amount']);
                    $dt = ReimbursementMedicalExpense::create($payload);
                }
            }

//             $user = \App\User::where('id',$data->id_user)->first();
//             $curl = \Curl::to('https://api.fonnte.com/send')
//                         ->withHeaders([
//                             'Authorization: G-BJE9txd#aXDewvme7u'
//                         ])
//                         ->withData([
//                             'target' => $user->phoneNumber,
//                             'message' => "Hai *".$user->name."*,\n\nPengajuan reimbursement Anda dengan *".$data->no_reimbursement."* sebesar *Rp ".number_format($data->nominal_pengajuan,0,',','.')."* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Direktur Operasional.\n\nTerima kasih.
// \n\nKlik untuk melihat detail pengajuan : ".url('/reimbursement-medical/'.$data->id)
//                         ])
//                         ->post();

//             $dirops = \App\User::where('jabatan','Direktur Operasional')->get();

//             foreach ($dirops as $value) {
                
//                 $curl = \Curl::to('https://api.fonnte.com/send')
//                         ->withHeaders([
//                             'Authorization: G-BJE9txd#aXDewvme7u'
//                         ])
//                         ->withData([
//                             'target' => $value->phoneNumber,
//                             'message' => "Hai *".$value->name."*,\n\nPengajuan reimbursement Anda dengan *".$data->no_reimbursement."* sebesar *Rp ".number_format($data->nominal_pengajuan,0,',','.')."* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
// \n\nKlik untuk melihat detail pengajuan : ".url('/reimbursement-medical/'.$data->id)
//                         ])
//                         ->post();
    

//             }


            DB::commit();
            return redirect()->back()->with(['success' => 'Reimbursement Berhasil Diajukan']);

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
        //
        $data = Reimbursement::find($id);

        return view('reimbursement-medical.detail',[
            'data' => $data
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
        ActivityLogger::log(
            'reimbursement-medical',
            'approve',
            'Reimbursement medical disetujui',
            $data->no_reimbursement,
            'reimbursement',
            $data->id,
            ['status' => $data->status]
        );
        return redirect()->back()->with(['success' => "Berhasil disetujui"]);
    }
}
