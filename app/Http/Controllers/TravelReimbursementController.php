<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\TravelType;
use App\User;
use App\TravelTripType;
use App\TravelTripRate;
use App\TravelHotelCondition;
use App\ReimbursementDetail;
use App\ReimbursementTravel;
use App\ReimbursementTravelDetail;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
use Redirect;

class TravelReimbursementController extends Controller
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
                        ->where('reimbursement.reimbursement_type',2);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.id_user', $id_user);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status',$request->status)->where('reimbursement.id_user', $id_user);    
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
                } 
                elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                }
                elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                }
                elseif ($data->status == 9){
                    if($data->mengetahui_op=='-') {
                        $meng = 'HEAD DEPT';
                    } else if($data->mengetahui_finance=='-') {
                        $meng = 'HR GA';
                    } else if($data->mengetahui_owner=='-') {
                        $meng = 'FINANCE';
                    } 
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                } elseif ($data->status == 10){
                    $button = '<button  class="view btn btn-warning btn-sm">DRAFT</button>';
                } else {
                  $button = '';
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
                return "<a href='".route('reimbursement-travel.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action', 'checkbox' ,'nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }
        
        $check_approval  = DB::select( DB::raw("SELECT count(id) AS id FROM users WHERE id_approval = '$id_user'"))['0']->id;

        return view('reimbursement-travel.index',[
            'check_approval' => $check_approval,
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
        ]);
    }
    
    
    public function approval(Request $request)
    {
        if(request()->ajax())
        {
            $id_user = auth()->user()->id;           
            
            if(auth()->user()->jabatan=='Finance' || auth()->user()->jabatan=='Owner' || auth()->user()->jabatan=='superadmin') {
                $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10)->where('users.id_approval', $id_user);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10)->where('reimbursement.status',$request->status)->where('users.id_approval', $id_user);    
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

            if(isset($request->user_id) && $request->user_id != "") {
                $data = $data->where('reimbursement.id_user','=',$request->user_id);
            }
            
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            

            $data = $data->orderBy('reimbursement.id', 'DESC');
            return datatables()->of($data)
            ->addColumn('action', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                } elseif ($data->status == 1) {
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
                } else {
                  $button = '';
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
                return "<a href='".route('reimbursement-travel.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action', 'checkbox','nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }

        return view('reimbursement-travel.approval',[
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
        
        $tripTypes = TravelTripType::where('type','LOCAL')->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();

        return view('reimbursement-travel.create',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createOverseas()
    {
        
        $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();

        return view('reimbursement-travel.create-overseas',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition
        ]);

    }

    
    public function store(Request $request)
    {
        DB::beginTransaction();
        $id_max  = DB::select( DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id + 1;
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; // DRAFT
            $notif = 'Reimbursement Successfully Saved as Draft';
        } else if (isset($_POST['save_item'])) {
            $status = 10;
            $notif = 'redirect';
        }
        try {
            $total = 0;
            foreach ($request->reimburse as $key => $value) {
                $total += (int) str_replace(".","",$value['total']);
            }            

            $data = [
                "id_user" => auth()->user()->id,
                // "no_reimbursement" => "UUDP-REIMBURSE-T-00".(Reimbursement::where('no_reimbursement','like',"%-T-%")->count()+1),
                "no_reimbursement" => "UUDP-REIMBURSE-T-00".$id_max,
                "date" => $request->reimburse['0']['date'],
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => $total,
                "status" => $status,
                "reimbursement_type" => 2,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark,
                "travel_type" => $request->travel_type,
                "idr_rate" => str_replace(".","",$request->idr_rate),
                "usd_rate" => str_replace(".","",$request->usd_rate),
                "jpy_rate" => str_replace(".","",$request->jpy_rate),
            ];
    
            $data = Reimbursement::create($data);
            foreach ($request->rates as $key => $value) {
                TravelTripRate::create([
                    'reimbursement_id' => $data->id,
                    'currency' => $value['code'],
                    'rate' => str_replace(".", "", $value['rate']),
                ]);
            }
            foreach ($request->reimburse as $key => $value) {
                $payload = [
                    'reimbursement_id' => $data->id,
                    'date' => $value['date'],
                    'purpose' => $value['purpose'],
                    'trip_type_id' => $value['trip_type_id'],
                    'hotel_condition_id' => $value['hotel_condition_id'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'allowance' => str_replace(".","",$value['allowance']),
                    'total' => str_replace(".",'',$value['total']),
                ];
    
                $dt = ReimbursementTravel::create($payload);
                foreach ($value['detail'] as $k => $v) {
                    if (isset($v['cost_type_id'])) {
                        
                    $payloadDetail = [
                        'reimbursement_id' => $id_max,
                        'reimbursement_travel_id' => $dt->id,
                        'destination' => $v['destination'],
                        'payment_type' => $v['payment_type'],
                        'cost_type_id' => $v['cost_type_id'],
                        'currency' => $v['currency'],
                        'amount' => str_replace(".","",$v['amount']),
                        'idr_rate' => str_replace(".","",$v['idr_rate']),
                        'tax' => str_replace(".","",$v['tax']),
                    ];
                    
                    if(isset($v['proof'])) {
                        $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.proof');
                        $new_name = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $new_name);
                        $payloadDetail['evidence'] = $new_name;
                    }
        
                    if(isset($v['file'])) {
                        $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.file');
                        $new_name = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $new_name);
                        $payloadDetail['evidence'] = $new_name;
                    }
                    $da = ReimbursementTravelDetail::create($payloadDetail);
                    }
                }
            }

            $id_main = DB::select( DB::raw("SELECT max(id) as id_main FROM reimbursement"))['0']->id_main;
            $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
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
                            url('/reimbursement-travel/' . $data->id),
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
                            url('/reimbursement-travel/' . $data->id),
                    ])->post();
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/');
            }

            

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

    public function saveItem(Request $request, $id_main)
    {

        DB::beginTransaction();
        
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; // DRAFT
            $notif = 'Reimbursement Successfully Saved as Draft';
        } else if (isset($_POST['save_item'])) {
            $status = 10;
            $notif = 'redirect';
        }


        try {
            // $total = 0;
            // foreach ($request->reimburse as $key => $value) {
            //     $total += (int) str_replace(".","",$value['total']);
            // }            
            
            // foreach ($request->reimburse as $key => $value) {
            //     $payload = [
            //         'reimbursement_id' => $id_main,
            //         'date' => $value['date'],
            //         'purpose' => $value['purpose'],
            //         'trip_type_id' => $value['trip_type_id'],
            //         'hotel_condition_id' => $value['hotel_condition_id'],
            //         'start_time' => $value['start_time'],
            //         'end_time' => $value['end_time'],
            //         'allowance' => str_replace(".","",$value['allowance']),
            //         'total' => str_replace(".",'',$value['total']),
            //     ];
    
            //     $dt = ReimbursementTravel::create($payload);
            //     foreach ($value['detail'] as $k => $v) {
            //         if (isset($v['cost_type_id'])) {
                        
            //         $payloadDetail = [
            //             'reimbursement_id' => $id_main,
            //             'reimbursement_travel_id' => $dt->id,
            //             'destination' => $v['destination'],
            //             'payment_type' => $v['payment_type'],
            //             'cost_type_id' => $v['cost_type_id'],
            //             'currency' => $v['currency'],
            //             'amount' => str_replace(".","",$v['amount']),
            //             'idr_rate' => str_replace(".","",$v['idr_rate']),
            //             'tax' => str_replace(".","",$v['tax']),
            //         ];
                    
            //         if(isset($v['proof'])) {
            //             $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.proof');
            //             $new_name = rand() . '.' . $image->getClientOriginalExtension();
            //             $image->move(public_path('images/file_bukti'), $new_name);
            //             $payloadDetail['evidence'] = $new_name;
            //         }
        
            //         if(isset($v['file'])) {
            //             $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.file');
            //             $new_name = rand() . '.' . $image->getClientOriginalExtension();
            //             $image->move(public_path('images/file_bukti'), $new_name);
            //             $payloadDetail['evidence'] = $new_name;
            //         }
            //         $da = ReimbursementTravelDetail::create($payloadDetail);
            //         }
            //     }
            // }

            $remark = $request->remark;
            $reimbursement_department_id = $request->reimbursement_department_id;
            
            if(empty($request->currency)) {
              return response()->json(['status' => 'currency_empty']);  
            }
            
            //Update table reimbursement
            
            $form_data = array(
                'remark'        =>  $request->remark,
                'reimbursement_department_id'        =>  $request->reimbursement_department_id,
                'date'        =>  $request->date,
            ); 
            
            Reimbursement::whereId($id_main)->update($form_data);

            // Insert table reimbursement_travel

            $form_travel = array(
                'reimbursement_id'        =>  $id_main,
                'date'        =>  $request->date,
                'purpose'        =>  $request->purpose,
                'trip_type_id'        =>  $request->trip_type_id,
                'hotel_condition_id'        =>  $request->hotel_condition_id,
                'start_time'        =>  $request->start_time,
                'end_time'        =>  $request->end_time,
                'allowance'        =>  str_replace(".", "", $request->allowance),
                'total'        =>  str_replace(".", "", $request->nominal_pengajuan),
            );

            DB::table('reimbursement_travel')->insert($form_travel);

            $id_detail = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
                
            $count_ = count($request->currency);
            
            for ($i=0; $i < $count_; $i++) {
              
                if(empty($request->proof[$i]) && empty($request->file[$i])) {
                    $id_detail_ = $request->id_detail[$i];
                    $evidence  = DB::select( DB::raw("SELECT evidence FROM reimbursement_travel_details WHERE id='$id_detail_'"))['0']->evidence;  
                } else if(empty($request->proof[$i]) && !empty($request->file[$i])) {
                    $image = $request->file[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                    
                } else if(empty($request->file[$i]) && !empty($request->proof[$i])) {
                    $image = $request->proof[$i];
                    $evidence = rand() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/file_bukti'), $evidence);
                } 
              
                $new = new ReimbursementTravelDetail;
                $new->reimbursement_id = $id_main;
                $new->reimbursement_travel_id = $id_detail;
                $new->cost_type_id = $request->cost_type_id[$i];
                $new->destination = $request->destination[$i];
                $new->payment_type = $request->payment_type[$i];
                $new->currency = $request->currency[$i];
                $new->idr_rate = str_replace(".", "", $request->idr_rate[$i]);
                $new->amount = str_replace(".", "", $request->amount[$i]);
                $new->tax = str_replace(".", "", $request->tax[$i]);
                $new->evidence = $evidence;
                $new->status = 1;
                $new->save();
            }

            $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            
            $form_data = array(
                'status'        =>  $status,
                'nominal_pengajuan' =>  str_replace(".", "", $total),
            );
            
            Reimbursement::where('id', $id_main)->update($form_data);

            $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
                'status'        =>  $status,
                'nominal_pengajuan' =>  str_replace(".", "", $total),
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);
            $data = Reimbursement::find($id_main);
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
                            url('/reimbursement-travel/' . $data->id),
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
                            url('/reimbursement-travel/' . $data->id),
                    ])->post();
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/');
            }

            

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

    public function show($id)
    {
        $data = Reimbursement::find($id);
        $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, allowance_cash, metode_allowance, metode_cash FROM reimbursement WHERE id = '$id'"));
        $bdc = $cek['0']->total_bdc;
        $cash = $cek['0']->total_cash;
        $allowance = $cek['0']->allowance_cash;
        $metode_allowance_ = $cek['0']->metode_allowance;
        $metode_cash_ = $cek['0']->metode_cash;
        
        if ($metode_allowance_ == null) {
            $metode_allowance = "";
        } else {
            $metode_allowance = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_allowance_'"))['0']->nama_list;  
        }

        if ($metode_cash_ == null) {
            $metode_cash = "";
        } else {
            $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
        }

        return view('reimbursement-travel.detail',[
            'data' => $data,
            'bdc' => $bdc,
            'cash' => $cash,
            'allowance' => $allowance,
            'metode_allowance' => $metode_allowance,
            'metode_cash' => $metode_cash,
        ]);
    }

    public function addItem($id_main, $id_travel)
    {
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id_main'"));
        $travel_type = $data['0']->travel_type;
        if ($travel_type == 'Domestic') {
            $tripTypes = TravelTripType::where('type','LOCAL')->get();  
            $file = 'add-item';  
        } else {
            $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
            $file = 'add-item-overseas';
        }
        
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        $item  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id_main'"));
        $id_reimb = $data['0']->id;
        $data_travel  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE id='$id_travel'"));
        $travel_trip  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_main'"));
        $id_detail = $id_travel;
        $travel_detail  = DB::select( DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"));
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb' "));

        return view('reimbursement-travel.'.$file.'',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
            "data_item" => $item,
            "travel_type" => $travel_type,
        ]);
    }

    public function addNewItem($id_main)
    {
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id_main'"));
        $travel_type = $data['0']->travel_type;
        if ($travel_type == 'Domestic') {
            $tripTypes = TravelTripType::where('type','LOCAL')->get();   
            $file = "add-new-item"; 
        } else {
            $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
            $file = "add-new-item-overseas";
        }
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        
        
        $item  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id_main'"));
        $id_reimb = $data['0']->id;
        $data_travel  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id_main'"));
        $travel_trip  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_main'"));
        $id_detail = $data_travel['0']->id;
        $travel_detail  = DB::select( DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"));
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb'"));

        return view('reimbursement-travel.'.$file.'',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
            "data_item" => $item,
            "travel_type" => $travel_type,
        ]);
    }

    public function editInquiry($id)
    {

        $tripTypes = TravelTripType::where('type','LOCAL')->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id'"));
        $item  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id'"));
        $id_reimb = $data['0']->id;
        $data_travel  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id'"));
        $travel_trip  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id'"));
        $id_detail = $data_travel['0']->id;
        $travel_detail  = DB::select( DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"));
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb'"));
        
        
        return view('reimbursement-travel.edit-inquiry',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
            "item" => $item,
        ]);
    }
    
    public function editOverseas($id)
    {

        $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id'"));
        $id_reimb = $data['0']->id;
        $data_travel  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id='$id'"));
        $travel_trip  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id'"));
        $id_detail = $data_travel['0']->id;
        $travel_detail  = DB::select( DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"));
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb'"));
        
        
        return view('reimbursement-travel.edit-overseas',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
        ]);
    }

    
    public function updateInquiry(Request $request, $id)
    {
        
        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;
        
        if(empty($request->currency)) {
          return response()->json(['status' => 'currency_empty']);  
        }
        
        //Update table reimbursement
        
        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        ); 
        
        Reimbursement::whereId($id)->update($form_data);
        
        //Update table travel_trip_rates
        
        $count = count($request->currency_rate);
        
        $delete  = DB::select( DB::raw("DELETE FROM travel_trip_rates WHERE reimbursement_id = '$id'"));
        
        for ($i=0; $i < $count; $i++) {
          $new = new TravelTripRate;
          $new->reimbursement_id = $id;
          $new->currency = $request->currency_rate[$i];
          $new->rate = str_replace(".", "", $request->rate[$i]);
          $new->save();
        }
        
        //Update table  reimbursement_travel

        if ($request->travel_type=='Domestic') {
            $allowance = str_replace(".","",$request->allowance);
        } else {
            $allowance = str_replace(".","",$request->allowance);
        }
        
        $form_data = array(
            'purpose'        =>  $request->remark,
            'trip_type_id'        =>  $request->trip_type_id,
            'hotel_condition_id'        =>  $request->hotel_condition_id,
            'start_time'        =>  $request->start_time,
            'end_time'        =>  $request->end_time,
            'allowance'        =>  str_replace(".", "", $allowance),
        );
        
        ReimbursementTravel::where('reimbursement_id', $id)->update($form_data);
        
        //Update table  reimbursement_travel_details
        
        $count_ = count($request->currency);
        
        $id_detail  = DB::select( DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id = '$id'"))['0']->id;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));
        
        for ($i=0; $i < $count_; $i++) {
          
            if(empty($request->proof[$i]) && empty($request->file[$i])) {
                $id_detail_ = $request->id_detail[$i];
                $evidence  = DB::select( DB::raw("SELECT evidence FROM reimbursement_travel_details WHERE id='$id_detail_'"))['0']->evidence;  
            } else if(empty($request->proof[$i]) && !empty($request->file[$i])) {
                $image = $request->file[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
                
            } else if(empty($request->file[$i]) && !empty($request->proof[$i])) {
                $image = $request->proof[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
            } 
          
            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = $request->cost_type_id[$i];
            $new->destination = $request->destination[$i];
            $new->payment_type = $request->payment_type[$i];
            $new->currency = $request->currency[$i];
            $new->idr_rate = str_replace(".", "", $request->idr_rate[$i]);
            $new->amount = str_replace(".", "", $request->amount[$i]);
            $new->tax = str_replace(".", "", $request->tax[$i]);
            $new->evidence = $evidence;
            $new->status = 1;
            $new->save();
        }
        
        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));
        
        $form_data = array(
            'status'        =>  0,
            'nominal_pengajuan' =>  str_replace(".", "", $request->nominal_pengajuan),
        );
        
        Reimbursement::where('id', $id)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id)->update($form_report);

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
                    url('/reimbursement-travel/' . $data->id),
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
                    "*,\n\nPengajuan reimbursement nama *".$user->name."*  dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-travel/' . $data->id),
            ])->post();
        
        return redirect('reimbursement-travel')->with(['success' => 'Reimbursement Berhasil Diajukan Kembali']);
    }

    public function updateItem(Request $request, $id_main, $id_travel)
    {
        

        if($request->id_user == $request->id_editor) {
          if (isset($_POST['save'])) {
            $status = 0;
            $return = redirect('reimbursement-travel')->with(['success' => "Reimbursement Successfully Submitted"]);
          } else if (isset($_POST['save_draft'])) {
              $status = 10; // DRAFT
              $return = redirect()->back()->with(['success' => "Reimbursement Successfully Saved as Draft"]);
          } else if (isset($_POST['save_item'])) {
              $status = 10;
              $return = redirect('reimbursement-travel/add-item/'.$id_main.'');
          }
        } else {
          
			if (isset($_POST['save_owner'])) {
                $status = 3;
                $return = redirect()->back()->with(['success' => "Reimbursement Successfully Updated"]);
            } else if (isset($_POST['edit_owner'])) {
                $status = 2;
                $return =  redirect()->to('reimbursement-travel/add-item/'.$id_main.'/'.$id_travel.'')->with('success', 'Reimbursement Successfully Updated');
                //$return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            } else if (isset($_POST['edit_finance'])) {
                $status = 1;
                $return =  redirect()->to('reimbursement-travel/add-item/'.$id_main.'/'.$id_travel.'')->with('success', 'Reimbursement Successfully Updated');
                //$return = redirect()->back()->with(['success' => "Reimbursement Successfully Updated"]);
            } else if (isset($_POST['save_finance'])) {
                $status = 2;
                $return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            } else {
                $status = 0;
                $return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            }
          	
            
        }
        

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;
        
        if(empty($request->currency)) {
          return response()->json(['status' => 'currency_empty']);  
        }
        
        //Update table reimbursement
        
        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        ); 
        
        Reimbursement::whereId($id_main)->update($form_data);
        
        //Update table  reimbursement_travel

        if ($request->travel_type=='Domestic') {
            $allowance = str_replace(".","",$request->allowance);
        } else {
            $allowance = str_replace(".","",$request->allowance);
        }
        
        $form_data = array(
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $request->trip_type_id,
            'hotel_condition_id'        =>  $request->hotel_condition_id,
            'start_time'        =>  $request->start_time,
            'end_time'        =>  $request->end_time,
            'allowance'        =>  str_replace(".", "", $allowance),
            'total'        =>  str_replace(".", "", $request->nominal_pengajuan),
        );
        
        ReimbursementTravel::where('id', $id_travel)->update($form_data);
        
        //Update table  reimbursement_travel_details
        
        $count_ = count($request->currency);
       
        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));
        
        for ($i=0; $i < $count_; $i++) {
          
            if(empty($request->proof[$i]) && empty($request->file[$i])) {
                $id_detail_ = $request->id_detail[$i];
                $evidence  = DB::select( DB::raw("SELECT evidence FROM reimbursement_travel_details WHERE id='$id_detail_'"))['0']->evidence;  
            } else if(empty($request->proof[$i]) && !empty($request->file[$i])) {
                $image = $request->file[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
                
            } else if(empty($request->file[$i]) && !empty($request->proof[$i])) {
                $image = $request->proof[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
            } 
          
            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = $request->cost_type_id[$i];
            $new->destination = $request->destination[$i];
            $new->payment_type = $request->payment_type[$i];
            $new->currency = $request->currency[$i];
            $new->idr_rate = str_replace(".", "", $request->idr_rate[$i]);
            $new->amount = str_replace(".", "", $request->amount[$i]);
            $new->tax = str_replace(".", "", $request->tax[$i]);
            $new->evidence = $evidence;
            $new->status = 1;
            $new->save();
        }
        
        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        
        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  str_replace(".", "", $total),
        );
        
        Reimbursement::where('id', $id_main)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
        if($request->id_user == $request->id_editor) {
          if ($status==0) {
              $data = Reimbursement::find($id_main);
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
                          "* telah diajukan.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                          \n\nKlik untuk melihat detail pengajuan : " .
                          url('/reimbursement-travel/' . $data->id),
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
                          url('/reimbursement-travel/' . $data->id),
                  ])->post();
          }
        }
            
        return $return;
    }
  
    public function updateItemReject(Request $request, $id_main, $id_travel)
    {
        if (isset($_POST['save_again'])) {
            $status = 0;
            $return = redirect('reimbursement-travel')->with(['success' => "Reimbursement Successfully Submitted Again"]);
        } else if (isset($_POST['save_finance'])) {
            $status = 1;
            $return = back()->with(['success' => "Reimbursement Successfully Updated"]);
        } else {
            $status = 9;  
            $return = back()->with(['success' => "Reimbursement Successfully Updated"]);
        }
        
        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;
        
        if(empty($request->currency)) {
          return response()->json(['status' => 'currency_empty']);  
        }
        
        //Update table reimbursement
        
        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        ); 
        
        Reimbursement::whereId($id_main)->update($form_data);
        
        //Update table  reimbursement_travel

        if ($request->travel_type=='Domestic') {
            $allowance = str_replace(".","",$request->allowance);
        } else {
            $allowance = str_replace(".","",$request->allowance);
        }
        
        $form_data = array(
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $request->trip_type_id,
            'hotel_condition_id'        =>  $request->hotel_condition_id,
            'start_time'        =>  $request->start_time,
            'end_time'        =>  $request->end_time,
            'allowance'        =>  str_replace(".", "", $allowance),
            'total'        =>  str_replace(".", "", $request->nominal_pengajuan),
        );
      
        
        ReimbursementTravel::where('id', $id_travel)->update($form_data);
        
        //Update table  reimbursement_travel_details
        
        $count_ = count($request->currency);
       
        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));
        
        for ($i=0; $i < $count_; $i++) {
          
            if(empty($request->proof[$i]) && empty($request->file[$i])) {
                $id_detail_ = $request->id_detail[$i];
                $evidence  = DB::select( DB::raw("SELECT evidence FROM reimbursement_travel_details WHERE id='$id_detail_'"))['0']->evidence;  
            } else if(empty($request->proof[$i]) && !empty($request->file[$i])) {
                $image = $request->file[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
                
            } else if(empty($request->file[$i]) && !empty($request->proof[$i])) {
                $image = $request->proof[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
            } 
          
            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = $request->cost_type_id[$i];
            $new->destination = $request->destination[$i];
            $new->payment_type = $request->payment_type[$i];
            $new->currency = $request->currency[$i];
            $new->idr_rate = str_replace(".", "", $request->idr_rate[$i]);
            $new->amount = str_replace(".", "", $request->amount[$i]);
            $new->tax = str_replace(".", "", $request->tax[$i]);
            $new->evidence = $evidence;
            $new->status = 1;
            $new->save();
        }
        
        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        
        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  str_replace(".", "", $total),
        );
        
        Reimbursement::where('id', $id_main)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
      
        if ($status==0) {
              $data = Reimbursement::find($id_main);
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
                          url('/reimbursement-travel/' . $data->id),
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
                          url('/reimbursement-travel/' . $data->id),
                  ])->post();
        }
            
        return $return;
    }

    public function updateItemApproval(Request $request, $id_main, $id_travel)
    {
        if (auth()->user()->jabatan=='Direktur Operasional') {
            $status = 1;
        } else if (auth()->user()->jabatan=='Finance') {
            $status = 2;
        } else {
            $status = 3;
        }

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;
        
        if(empty($request->currency)) {
          return response()->json(['status' => 'currency_empty']);  
        }
        
        //Update table reimbursement
        
        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        ); 
        
        Reimbursement::whereId($id_main)->update($form_data);
        
        //Update table  reimbursement_travel

        if ($request->travel_type=='Domestic') {
            $allowance = str_replace(".","",$request->allowance);
        } else {
            $allowance = str_replace(".","",$request->allowance);
        }
        
        $form_data = array(
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $request->trip_type_id,
            'hotel_condition_id'        =>  $request->hotel_condition_id,
            'start_time'        =>  $request->start_time,
            'end_time'        =>  $request->end_time,
            'allowance'        =>  str_replace(".", "", $allowance),
            'total'        =>  str_replace(".", "", $request->nominal_pengajuan),
        );
        
        ReimbursementTravel::where('id', $id_travel)->update($form_data);
        
        //Update table  reimbursement_travel_details
        
        $count_ = count($request->currency);
       
        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));
        
        for ($i=0; $i < $count_; $i++) {
          
            if(empty($request->proof[$i]) && empty($request->file[$i])) {
                $id_detail_ = $request->id_detail[$i];
                $evidence  = DB::select( DB::raw("SELECT evidence FROM reimbursement_travel_details WHERE id='$id_detail_'"))['0']->evidence;  
            } else if(empty($request->proof[$i]) && !empty($request->file[$i])) {
                $image = $request->file[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
                
            } else if(empty($request->file[$i]) && !empty($request->proof[$i])) {
                $image = $request->proof[$i];
                $evidence = rand() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/file_bukti'), $evidence);
            } 
          
            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = $request->cost_type_id[$i];
            $new->destination = $request->destination[$i];
            $new->payment_type = $request->payment_type[$i];
            $new->currency = $request->currency[$i];
            $new->idr_rate = str_replace(".", "", $request->idr_rate[$i]);
            $new->amount = str_replace(".", "", $request->amount[$i]);
            $new->tax = str_replace(".", "", $request->tax[$i]);
            $new->evidence = $evidence;
            $new->status = 1;
            $new->save();
        }
        
        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        
        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  str_replace(".", "", $total),
        );
        
        Reimbursement::where('id', $id_main)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
        
        $data = Reimbursement::find($id_main);
        $user = \App\User::where('id', $data->id_user)->first();

        if (auth()->user()->jabatan=='Direktur Operasional') {
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
                    auth()->user()->name  .
                    " (Head Department)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh HR GA.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
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
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
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
                    $user->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diterima oleh *" .
                    auth()->user()->name  .
                    " (HR GA)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
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
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
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
                    $user->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diterima oleh *" .
                    auth()->user()->name  .
                    " (Finance)* .\n\nSaat ini sedang menunggu Proses Pencairan oleh Finance.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
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
                            "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])
                    ->post();
            }
        }
        
        return redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);;
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

        if (isset($request->selected)) {

            $selected =  explode(',', $request->selected);
            $data = Reimbursement::select('*', 'reimbursement.date AS tgl')->orderBy('reimbursement.no_reimbursement','desc')->whereIn('id', $selected);
            $head_dept = $data->first()->mengetahui_op;
          
            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total')->whereIn('id', $selected);
            $allowance_bdc = Reimbursement::selectRaw('SUM(allowance_bdc) as total')->whereIn('id', $selected); 
            $simcard_bdc = Reimbursement::selectRaw('SUM(simcard_bdc) as total')->whereIn('id', $selected); 
            $flight_bdc = Reimbursement::selectRaw('SUM(flight_bdc) as total')->whereIn('id', $selected); 
            $rentalcar_bdc = Reimbursement::selectRaw('SUM(rentalcar_bdc) as total')->whereIn('id', $selected); 
            $hotel_bdc = Reimbursement::selectRaw('SUM(hotel_bdc) as total')->whereIn('id', $selected); 
            $toll_bdc = Reimbursement::selectRaw('SUM(toll_bdc) as total')->whereIn('id', $selected); 
            $gasoline_bdc = Reimbursement::selectRaw('SUM(gasoline_bdc) as total')->whereIn('id', $selected); 
            $taxi_bdc = Reimbursement::selectRaw('SUM(taxi_bdc) as total')->whereIn('id', $selected); 
            $train_bdc = Reimbursement::selectRaw('SUM(train_bdc) as total')->whereIn('id', $selected); 
            $tax_bdc = Reimbursement::selectRaw('SUM(tax_bdc) as total')->whereIn('id', $selected); 
            $others_bdc = Reimbursement::selectRaw('SUM(others_bdc) as total')->whereIn('id', $selected); 
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total')->whereIn('id', $selected); 
            $allowance_cash = Reimbursement::selectRaw('SUM(allowance_cash) as total')->whereIn('id', $selected); 
            $simcard_cash = Reimbursement::selectRaw('SUM(simcard_cash) as total')->whereIn('id', $selected); 
            $flight_cash = Reimbursement::selectRaw('SUM(flight_cash) as total')->whereIn('id', $selected); 
            $rentalcar_cash = Reimbursement::selectRaw('SUM(rentalcar_cash) as total')->whereIn('id', $selected); 
            $hotel_cash = Reimbursement::selectRaw('SUM(hotel_cash) as total')->whereIn('id', $selected); 
            $toll_cash = Reimbursement::selectRaw('SUM(toll_cash) as total')->whereIn('id', $selected); 
            $gasoline_cash = Reimbursement::selectRaw('SUM(gasoline_cash) as total')->whereIn('id', $selected); 
            $taxi_cash = Reimbursement::selectRaw('SUM(taxi_cash) as total')->whereIn('id', $selected); 
            $train_cash = Reimbursement::selectRaw('SUM(train_cash) as total')->whereIn('id', $selected); 
            $tax_cash = Reimbursement::selectRaw('SUM(tax_cash) as total')->whereIn('id', $selected); 
            $others_cash = Reimbursement::selectRaw('SUM(others_cash) as total')->whereIn('id', $selected); 


        } else {

            $data = Reimbursement::select('*', 'reimbursement.date AS tgl')->orderBy('reimbursement.no_reimbursement','desc');
            $id_user = $_GET['driver'];
            $head_dept = DB::select( DB::raw("SELECT nama_approval FROM users WHERE id = '$id_user'"))['0']->nama_approval;
            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total');
            $allowance_bdc = Reimbursement::selectRaw('SUM(allowance_bdc) as total'); 
            $simcard_bdc = Reimbursement::selectRaw('SUM(simcard_bdc) as total'); 
            $flight_bdc = Reimbursement::selectRaw('SUM(flight_bdc) as total'); 
            $rentalcar_bdc = Reimbursement::selectRaw('SUM(rentalcar_bdc) as total'); 
            $hotel_bdc = Reimbursement::selectRaw('SUM(hotel_bdc) as total'); 
            $toll_bdc = Reimbursement::selectRaw('SUM(toll_bdc) as total'); 
            $gasoline_bdc = Reimbursement::selectRaw('SUM(gasoline_bdc) as total'); 
            $taxi_bdc = Reimbursement::selectRaw('SUM(taxi_bdc) as total'); 
            $train_bdc = Reimbursement::selectRaw('SUM(train_bdc) as total'); 
            $tax_bdc = Reimbursement::selectRaw('SUM(tax_bdc) as total'); 
            $others_bdc = Reimbursement::selectRaw('SUM(others_bdc) as total'); 
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total'); 
            $allowance_cash = Reimbursement::selectRaw('SUM(allowance_cash) as total'); 
            $simcard_cash = Reimbursement::selectRaw('SUM(simcard_cash) as total'); 
            $flight_cash = Reimbursement::selectRaw('SUM(flight_cash) as total'); 
            $rentalcar_cash = Reimbursement::selectRaw('SUM(rentalcar_cash) as total'); 
            $hotel_cash = Reimbursement::selectRaw('SUM(hotel_cash) as total'); 
            $toll_cash = Reimbursement::selectRaw('SUM(toll_cash) as total'); 
            $gasoline_cash = Reimbursement::selectRaw('SUM(gasoline_cash) as total'); 
            $taxi_cash = Reimbursement::selectRaw('SUM(taxi_cash) as total'); 
            $train_cash = Reimbursement::selectRaw('SUM(train_cash) as total'); 
            $tax_cash = Reimbursement::selectRaw('SUM(tax_cash) as total'); 
            $others_cash = Reimbursement::selectRaw('SUM(others_cash) as total'); 
           

            
            if(isset($request->start))
                $data = $data->whereDate('reimbursement.created_at','>=',$request->start);
                $bdc = $bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $allowance_bdc = $allowance_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $simcard_bdc = $simcard_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $flight_bdc = $flight_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $rentalcar_bdc = $rentalcar_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $hotel_bdc = $hotel_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $toll_bdc = $toll_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $gasoline_bdc = $gasoline_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $taxi_bdc = $taxi_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $train_bdc = $train_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $tax_bdc = $tax_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $others_bdc = $others_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $total_cash = $total_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $allowance_cash = $allowance_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $simcard_cash = $simcard_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $flight_cash = $flight_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $rentalcar_cash = $rentalcar_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $hotel_cash = $hotel_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $toll_cash = $toll_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $gasoline_cash = $gasoline_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $taxi_cash = $taxi_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $train_cash = $train_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $tax_cash = $tax_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $others_cash = $others_cash->whereDate('reimbursement.created_at','>=',$request->start);

                
            if(isset($request->end))
                $data = $data->whereDate('reimbursement.created_at','<=',$request->end);
                $bdc = $bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $allowance_bdc = $allowance_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $simcard_bdc = $simcard_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $flight_bdc = $flight_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $rentalcar_bdc = $rentalcar_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $hotel_bdc = $hotel_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $toll_bdc = $toll_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $gasoline_bdc = $gasoline_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $taxi_bdc = $taxi_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $train_bdc = $train_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $tax_bdc = $tax_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $others_bdc = $others_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $total_cash = $total_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $allowance_cash = $allowance_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $simcard_cash = $simcard_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $flight_cash = $flight_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $rentalcar_cash = $rentalcar_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $hotel_cash = $hotel_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $toll_cash = $toll_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $gasoline_cash = $gasoline_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $taxi_cash = $taxi_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $train_cash = $train_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $tax_cash = $tax_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $others_cash = $others_cash->whereDate('reimbursement.created_at','<=',$request->end);

            if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
                $bdc = $bdc->where('reimbursement.status',$request->status);
                $allowance_bdc = $allowance_bdc->where('reimbursement.status',$request->status);
                $simcard_bdc = $simcard_bdc->where('reimbursement.status',$request->status);
                $flight_bdc = $flight_bdc->where('reimbursement.status',$request->status);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.status',$request->status);
                $hotel_bdc = $hotel_bdc->where('reimbursement.status',$request->status);
                $toll_bdc = $toll_bdc->where('reimbursement.status',$request->status);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.status',$request->status);
                $taxi_bdc = $taxi_bdc->where('reimbursement.status',$request->status);
                $train_bdc = $train_bdc->where('reimbursement.status',$request->status);
                $tax_bdc = $tax_bdc->where('reimbursement.status',$request->status);
                $others_bdc = $others_bdc->where('reimbursement.status',$request->status);
                $total_cash = $total_cash->where('reimbursement.status',$request->status);
                $allowance_cash = $allowance_cash->where('reimbursement.status',$request->status);
                $simcard_cash = $simcard_cash->where('reimbursement.status',$request->status);
                $flight_cash = $flight_cash->where('reimbursement.status',$request->status);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.status',$request->status);
                $hotel_cash = $hotel_cash->where('reimbursement.status',$request->status);
                $toll_cash = $toll_cash->where('reimbursement.status',$request->status);
                $gasoline_cash = $gasoline_cash->where('reimbursement.status',$request->status);
                $taxi_cash = $taxi_cash->where('reimbursement.status',$request->status);
                $train_cash = $train_cash->where('reimbursement.status',$request->status);
                $tax_cash = $tax_cash->where('reimbursement.status',$request->status);
                $others_cash = $others_cash->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "" && $request->driver != "null") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
                $bdc = $bdc->where('reimbursement.id_user','=',$request->driver);
                $allowance_bdc = $allowance_bdc->where('reimbursement.id_user','=',$request->driver);
                $simcard_bdc = $simcard_bdc->where('reimbursement.id_user','=',$request->driver);
                $flight_bdc = $flight_bdc->where('reimbursement.id_user','=',$request->driver);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.id_user','=',$request->driver);
                $hotel_bdc = $hotel_bdc->where('reimbursement.id_user','=',$request->driver);
                $toll_bdc = $toll_bdc->where('reimbursement.id_user','=',$request->driver);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.id_user','=',$request->driver);
                $taxi_bdc = $taxi_bdc->where('reimbursement.id_user','=',$request->driver);
                $train_bdc = $train_bdc->where('reimbursement.id_user','=',$request->driver);
                $tax_bdc = $tax_bdc->where('reimbursement.id_user','=',$request->driver);
                $others_bdc = $others_bdc->where('reimbursement.id_user','=',$request->driver);
                $total_cash = $total_cash->where('reimbursement.id_user','=',$request->driver);
                $allowance_cash = $allowance_cash->where('reimbursement.id_user','=',$request->driver);
                $simcard_cash = $simcard_cash->where('reimbursement.id_user','=',$request->driver);
                $flight_cash = $flight_cash->where('reimbursement.id_user','=',$request->driver);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.id_user','=',$request->driver);
                $hotel_cash = $hotel_cash->where('reimbursement.id_user','=',$request->driver);
                $toll_cash = $toll_cash->where('reimbursement.id_user','=',$request->driver);
                $gasoline_cash = $gasoline_cash->where('reimbursement.id_user','=',$request->driver);
                $taxi_cash = $taxi_cash->where('reimbursement.id_user','=',$request->driver);
                $train_cash = $train_cash->where('reimbursement.id_user','=',$request->driver);
                $tax_cash = $tax_cash->where('reimbursement.id_user','=',$request->driver);
                $others_cash = $others_cash->where('reimbursement.id_user','=',$request->driver);
            }
            
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
                $bdc = $bdc->where('reimbursement.id_user', auth()->user()->id);
                $allowance_bdc = $allowance_bdc->where('reimbursement.id_user', auth()->user()->id);
                $simcard_bdc = $simcard_bdc->where('reimbursement.id_user', auth()->user()->id);
                $flight_bdc = $flight_bdc->where('reimbursement.id_user', auth()->user()->id);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.id_user', auth()->user()->id);
                $hotel_bdc = $hotel_bdc->where('reimbursement.id_user', auth()->user()->id);
                $toll_bdc = $toll_bdc->where('reimbursement.id_user', auth()->user()->id);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.id_user', auth()->user()->id);
                $taxi_bdc = $taxi_bdc->where('reimbursement.id_user', auth()->user()->id);
                $train_bdc = $train_bdc->where('reimbursement.id_user', auth()->user()->id);
                $tax_bdc = $tax_bdc->where('reimbursement.id_user', auth()->user()->id);
                $others_bdc = $others_bdc->where('reimbursement.id_user', auth()->user()->id);
                $total_cash = $total_cash->where('reimbursement.id_user', auth()->user()->id);
                $allowance_cash = $allowance_cash->where('reimbursement.id_user', auth()->user()->id);
                $simcard_cash = $simcard_cash->where('reimbursement.id_user', auth()->user()->id);
                $flight_cash = $flight_cash->where('reimbursement.id_user', auth()->user()->id);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.id_user', auth()->user()->id);
                $hotel_cash = $hotel_cash->where('reimbursement.id_user', auth()->user()->id);
                $toll_cash = $toll_cash->where('reimbursement.id_user', auth()->user()->id);
                $gasoline_cash = $gasoline_cash->where('reimbursement.id_user', auth()->user()->id);
                $taxi_cash = $taxi_cash->where('reimbursement.id_user', auth()->user()->id);
                $train_cash = $train_cash->where('reimbursement.id_user', auth()->user()->id);
                $tax_cash = $tax_cash->where('reimbursement.id_user', auth()->user()->id);
                $others_cash = $others_cash->where('reimbursement.id_user', auth()->user()->id);
            }
        }
      
        if(count($data->get()) == 0) {
            echo "Data not found. Please make sure the <strong>search button has been clicked first</strong>.";
        } else {
          
          return view('print.travel-reimbursement',[
              'start_date' => $request->start,
              'end_date' => $request->end,
              'datas' => $data->get(),
              'head_dept' => $head_dept,
              'user' => User::find($request->driver),
              'bdc' => $bdc->get()['0']->total,
              'allowance_bdc' => $allowance_bdc->get()['0']->total,
              'simcard_bdc' => $simcard_bdc->get()['0']->total,
              'flight_bdc' => $flight_bdc->get()['0']->total,
              'rentalcar_bdc' => $rentalcar_bdc->get()['0']->total,
              'hotel_bdc' => $hotel_bdc->get()['0']->total,
              'toll_bdc' => $toll_bdc->get()['0']->total,
              'gasoline_bdc' => $gasoline_bdc->get()['0']->total,
              'taxi_bdc' => $taxi_bdc->get()['0']->total,
              'train_bdc' => $train_bdc->get()['0']->total,
              'tax_bdc' => $tax_bdc->get()['0']->total,
              'others_bdc' => $others_bdc->get()['0']->total,
              'total_cash' => $total_cash->get()['0']->total,
              'allowance_cash' => $allowance_cash->get()['0']->total,
              'simcard_cash' => $simcard_cash->get()['0']->total,
              'flight_cash' => $flight_cash->get()['0']->total,
              'rentalcar_cash' => $rentalcar_cash->get()['0']->total,
              'hotel_cash' => $hotel_cash->get()['0']->total,
              'toll_cash' => $toll_cash->get()['0']->total,
              'gasoline_cash' => $gasoline_cash->get()['0']->total,
              'taxi_cash' => $taxi_cash->get()['0']->total,
              'train_cash' => $train_cash->get()['0']->total,
              'tax_cash' => $tax_cash->get()['0']->total,
              'others_cash' => $others_cash->get()['0']->total

          ]);
          
        }
    }
    
    public function getCurrency($id, $cur)
    {
        $data  = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE currency='$cur' AND reimbursement_id='$id'"))['0']->rate;
        return response()->json(['data' => $data]);
    }
    
    public function getTripType($id)
    {
        $data  = DB::select( DB::raw("SELECT allowance,type,currency FROM travel_trip_types WHERE id='$id'"));
        return response()->json(['data' => $data]);
    }
    
    public function getTripTypeOverseas($id)
    {
        $data  = DB::select( DB::raw("SELECT rate FROM travel_trip_types WHERE id='$id'"))['0']->rate;
        return response()->json(['data' => $data]);
    }
    
    public function getTravelTripRates($id)
    {
        $data  = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id' AND currency='USD'"))['0']->rate;
        return response()->json(['data' => $data]);
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
                            url('/reimbursement-travel/' . $row->id),
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
                                    url('/reimbursement-travel/' . $row->id),
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
                            url('/reimbursement-travel/' . $row->id),
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
                                    url('/reimbursement-travel/' . $row->id),
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
                            url('/reimbursement-travel/' . $row->id),
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
                                    "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-travel/' . $row->id),
                            ])
                            ->post();

                    }
                } 
            }
        }

        return response()->json(['message' => 'Status updated & WA sent']);

    }
  
    public function storeItem(Request $request)
    {
        DB::beginTransaction();
        $id_max  = DB::select( DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id + 1;
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; // DRAFT
            $notif = 'redirect';
        } else if (isset($_POST['save_item'])) {
            $status = 10;
            $notif = 'redirect';
        }
        try {
            $total = 0;
            foreach ($request->reimburse as $key => $value) {
                $total += (int) str_replace(".","",$value['total']);
            }            

            $id_reimb =  Request::segment(3);
            
            // foreach ($request->rates as $key => $value) {
            //     TravelTripRate::create([
            //         'reimbursement_id' => $id_reimb,
            //         'currency' => $value['code'],
            //         'rate' => str_replace(".", "", $value['rate']),
            //     ]);
            // }
            foreach ($request->reimburse as $key => $value) {
                $payload = [
                    'reimbursement_id' => $id_reimb,
                    'date' => $value['date'],
                    'purpose' => $value['purpose'],
                    'trip_type_id' => $value['trip_type_id'],
                    'hotel_condition_id' => $value['hotel_condition_id'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'allowance' => str_replace(".","",$value['allowance']),
                    'total' => str_replace(".",'',$value['total']),
                ];

                $dt = ReimbursementTravel::create($payload);
                foreach ($value['detail'] as $k => $v) {
                    if (isset($v['cost_type_id'])) {
                        
                    $payloadDetail = [
                        'reimbursement_travel_id' => $dt->id,
                        'destination' => $v['destination'],
                        'payment_type' => $v['payment_type'],
                        'cost_type_id' => $v['cost_type_id'],
                        'currency' => $v['currency'],
                        'amount' => str_replace(".","",$v['amount']),
                        'idr_rate' => str_replace(".","",$v['idr_rate']),
                        'tax' => str_replace(".","",$v['tax']),
                    ];
                    
                    if(isset($v['proof'])) {
                        $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.proof');
                        $new_name = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $new_name);
                        $payloadDetail['evidence'] = $new_name;
                    }
        
                    if(isset($v['file'])) {
                        $image = $request->file('reimburse.'.$key.'.detail.'.$k.'.file');
                        $new_name = rand() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/file_bukti'), $new_name);
                        $payloadDetail['evidence'] = $new_name;
                    }
                    $da = ReimbursementTravelDetail::create($payloadDetail);
                    }
                }
            }

            $id_main = DB::select( DB::raw("SELECT max(id) as id_main FROM reimbursement"))['0']->id_main;
            $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);

            $user = \App\User::where('id', $id_reimb_user)->first();
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
                            url('/reimbursement-travel/' . $id_reimb),
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
                            url('/reimbursement-travel/' . $id_reimb),
                    ])->post();
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/');
            }

            

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

    public function updateCurrency(Request $request)
    {
        $id_rate = $request->id_rate;
        $currency = $request->currency;
        $rate = $request->rate;
        $reim_id = $request->reim_id;

        if ($id_rate == 0) {
            // Cek apakah data sudah ada
            $existing = TravelTripRate::where('reimbursement_id', $reim_id)->where('currency', $currency)->where('rate', $rate)->first();

            if (!$existing) {
                // Data belum ada, insert baru
                TravelTripRate::create([
                    'reimbursement_id' => $reim_id,
                    'currency' => $currency,
                    'rate' => $rate,
                ]);

                return response()->json(['message' => 'Data berhasil disimpan.']);
            } else {
                return response()->json(['message' => 'Data sudah ada, tidak disimpan.']);
            }
        } else {
            // Update data
            TravelTripRate::whereId($id_rate)->update([
                'currency' => $currency,
                'rate' => $rate,
            ]);

            return response()->json(['message' => 'Data berhasil diupdate.']);
        }
    }

    public function getCurrencyOptions(Request $request)
    {
        $selected = $request->selected;
        $reim_id = $request->reim_id;

        $currencyList = TravelTripRate::where('reimbursement_id', $reim_id)->get(); 

        $options = '<option value="">Pilih...</option>';
        foreach ($currencyList as $item) {
            $sel = $item->currency == $selected ? 'selected' : '';
            $options .= "<option value=\"{$item->currency}\" {$sel}>{$item->currency}</option>";
        }

        return response()->json(['options' => $options]);
    }

    public function deleteCurrencyOptions(Request $request)
    {
        $currency = $request->currency;
        $rate = $request->rate;
        $reim_id = $request->reim_id;

        // Cek dan hapus jika data ditemukan
        $deleted = TravelTripRate::where('reimbursement_id', $reim_id)
            ->where('currency', $currency)
            ->where('rate', $rate)
            ->delete();

        if ($deleted) {
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus.']);
        }
    }


    


    
}
