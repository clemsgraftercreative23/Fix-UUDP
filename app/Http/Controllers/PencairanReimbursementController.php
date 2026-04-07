<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\ReimbursementDetail;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use App\Kasbank;
use App\Api;
use App\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use DB;
class PencairanReimbursementController extends Controller
{
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
                        ->leftJoin('users','reimbursement.id_user','users.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan', 'users.name')
                        ->whereIn('status', [3,5])
                        ->orderBy('reimbursement.created_at', 'desc');


            if (isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at', '>=', $request->first);
            }

            if (isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at', '<=', $request->last);
            }

            if (isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status', $request->status);
            }

            if (isset($request->user_id) && $request->user_id != "") {
                $data = $data->where('reimbursement.id_user', '=', $request->user_id);
            }

            if (isset($request->type) && $request->type != "") {
                $data = $data->where('reimbursement.reimbursement_type', '=', $request->type);
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
                $button = '<button  class="view btn btn-success btn-sm">APPROVED Direktur Operasional</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED Finance</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">PROCESS SETTLEMENT</button>';
                } elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                }elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLE</button>';
                }
                $button .= '&nbsp;&nbsp;';

                return $button;

            })
            ->editColumn('no_project', function ($data) {
                if($data->id_project == null) {
                    return $data->remark;
                }
                return $data->no_project;
            })
            ->addColumn('nominal_pengajuan', function ($data) {
                $button ='';
                $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                return $button;
            })
            ->editColumn('no_reimbursement', function ($data) {
                return "<a href='".route('pencairan-reimbursement.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->editColumn('reimbursement_type', function ($data) {
                if($data->reimbursement_type == 1 ){
                $type = 'DRIVER';
                }elseif ($data->reimbursement_type == 2) {
                $type = 'TRAVEL';
                } elseif ($data->reimbursement_type == 3) {
                $type = 'ENTERTAINMENT';
                } 
                
                $type .= '&nbsp;&nbsp;';

                return $type;
            })
            ->rawColumns(['action','nominal_pengajuan','no_reimbursement', 'reimbursement_type'])
            ->make(true);
        }

        return view('pencairan-reimbursement.index',[
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
        ]);
    }

    
    public function show($id)
    {
        $data = Reimbursement::find($id);
        $kasbank = Kasbank::get();
        $user = User::find($data->id_user);
        $empNo = $user->idKaryawan;
        
        if($data->reimbursement_type==1) {
            $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, allowance_cash, metode_allowance, metode_cash FROM reimbursement WHERE id = '$id'"));
            $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, metode_cash, metode_fleet, total_fleet FROM reimbursement WHERE id = '$id'"));

            $cash = $cek['0']->total_cash;
            $metode_cash_ = $cek['0']->metode_cash;
            $metode_fleet_ = $cek['0']->metode_fleet;
            if ($metode_cash_ == null) {
                $metode_cash = "";
                $metode_fleet = "";
            } else {
                $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;  
                $metode_fleet = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_fleet_'"))['0']->nama_list;    
            }

            $cash  = DB::select( DB::raw("SELECT sum(subtotal) AS total FROM reimbursement_driver WHERE reimbursement_id = '$id' AND payment_type='Cash'"))['0']->total;
            $fleet  = DB::select( DB::raw("SELECT sum(subtotal) AS total FROM reimbursement_driver WHERE reimbursement_id = '$id' AND payment_type='Fleet'"))['0']->total;
			
            return view('pencairan-reimbursement.detail',[
                'data' => $data,
                'kasbank' => $kasbank,
                'metode_cash' => $metode_cash,
                'metode_fleet' => $metode_fleet,
                'cash' => $cash,
                'fleet' => $fleet,
                'empNo' => $empNo,
            ]);    
        } else if($data->reimbursement_type==2) {
            $detail  = DB::select( DB::raw("SELECT * FROM reimbursement_travel WHERE reimbursement_id = '$id'"));
            $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, allowance_cash, metode_allowance, metode_cash FROM reimbursement WHERE id = '$id'"));
            // $bdc = $cek['0']->total_bdc;
            // $cash = $cek['0']->total_cash;
            // $allowance = $cek['0']->allowance_cash;
            $bdc  = DB::select( DB::raw("SELECT SUM(amount) AS bdc FROM reimbursement_travel LEFT JOIN reimbursement_travel_details ON reimbursement_travel.id = reimbursement_travel_details.reimbursement_travel_id WHERE reimbursement_travel.reimbursement_id = '$id' AND payment_type='BDC'"))['0']->bdc;
            $cash  = DB::select( DB::raw("SELECT SUM(amount) AS cash FROM reimbursement_travel LEFT JOIN reimbursement_travel_details ON reimbursement_travel.id = reimbursement_travel_details.reimbursement_travel_id WHERE reimbursement_travel.reimbursement_id = '$id' AND payment_type='Cash'"))['0']->cash;
            $allowance  = DB::select( DB::raw("SELECT SUM(allowance) AS allowance FROM reimbursement_travel WHERE reimbursement_id = '$id'"))['0']->allowance;
            

            $metode_cash_ = $cek['0']->metode_cash;
            if ($metode_cash_ == null) {
                $metode_cash = "";
            } else {
                $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
            }

            $metode_allowance_ = $cek['0']->metode_allowance;
            if ($metode_allowance_ == null) {
                $metode_allowance = "";
            } else {
                $metode_allowance = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_allowance_'"))['0']->nama_list;    
            }

            // $metode_allowance_ = $cek['0']->metode_allowance;
            // $metode_cash_ = $cek['0']->metode_cash;
            // $metode_allowance = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_allowance_'"))['0']->nama_list;
            // $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;


            $data = Reimbursement::find($id);
            return view('pencairan-reimbursement.detail_travel',[
                'data' => $data,
                'kasbank' => $kasbank,
                'detail' => $detail,
                'bdc' => $bdc,
                'cash' => $cash,
                'allowance' => $allowance,
                'metode_allowance' => $metode_allowance,
                'metode_cash' => $metode_cash,
                'empNo' => $empNo,

            ]);    
        } else if($data->reimbursement_type==3) {
            $detail  = DB::select( DB::raw("SELECT * FROM reimbursement_entertaiments WHERE reimbursement_id = '$id'"));

            $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, metode_cash FROM reimbursement WHERE id = '$id'"));

            $bdc = $cek['0']->total_bdc;
            $cash = $cek['0']->total_cash;
            $metode_cash_ = $cek['0']->metode_cash;
            if ($metode_cash_ == null) {
                $metode_cash = "";
            } else {
                $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
            }
            


            return view('pencairan-reimbursement.detail_entertainment',[
                'data' => $data,
                'kasbank' => $kasbank,
                'detail' => $detail,
                'bdc' => $bdc,
                'cash' => $cash,
                'metode_cash' => $metode_cash,
                'empNo' => $empNo,
            ]);    
        }
        
    }

    
    public function update(Request $request, $id)
    {
        //
        DB::beginTransaction();
        try {
            $data = Reimbursement::find($id);
            $id_user = $data->id_user;
            $id_department = DB::select( DB::raw("SELECT departmentId FROM users WHERE id='$id_user'"))['0']->departmentId;
            $nama_department  = DB::select( DB::raw("SELECT nama_departemen FROM departemen WHERE id='$id_department'"))['0']->nama_departemen;
            
            $department = \App\Departemen::find($data->reimbursement_department_id);
            $nominal = $data->nominal_pengajuan;
            $cek_type = DB::select(DB::raw("SELECT reimbursement_type FROM reimbursement WHERE id='$data->id'"))['0']->reimbursement_type;
            $token = Api::where('id', 1)->get()->pluck('token');
            $session = Api::where('id', 1)->get()->pluck('session');

            if($cek_type==2) {

                $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
                $detailJournalVoucher = [];
                // Total allowance
                $total_allowance = (int) str_replace(".", "", $request->total_allowance);
                $total_cash = (int) str_replace(".", "", $request->total_cash);

                // Allowance
                if ($total_allowance > 0) {
                    $detailJournalVoucher[] = [
                        'accountNo' => $request->akun_perkiraan_allowance,
                        'amount' => $total_allowance,
                        'amountType' => 'DEBIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];

                    $detailJournalVoucher[] = [
                        'accountNo' => $request->metode_allowance,
                        'amount' => $total_allowance,
                        'amountType' => 'CREDIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];
                }

                // Cash
                if ($total_cash > 0) {
                    $detailJournalVoucher[] = [
                        'accountNo' => $request->akun_perkiraan_cash,
                        'amount' => $total_cash,
                        'amountType' => 'DEBIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];

                    $detailJournalVoucher[] = [
                        'accountNo' => $request->metode_cash,
                        'amount' => $total_cash,
                        'amountType' => 'CREDIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];
                }

                // Payload only if there are journal details
                if (!empty($detailJournalVoucher)) {
                    $payload = [
                        'detailJournalVoucher' => $detailJournalVoucher,
                        'transDate' => date('d/m/Y'),
                        'description' => $data->no_reimbursement . " - " . $data->remark . " (CASH)",
                    ];
                }

                // $payload = array (
                //  'detailJournalVoucher' =>
                //      array (
                //      0 =>
                //      array (
                //          'accountNo' => $request->akun_perkiraan_allowance,
                //          'amount' => str_replace(".", "", $request->total_allowance),
                //          'amountType' => 'DEBIT',
                //          'subsidiaryType' => 'EMPLOYEE',
                //          'employeeNo' => $request->employeeNo,
                //          'departmentName' => $nama_department,
                //      ),
                //      1 =>
                //      array (
                //          'accountNo' => $request->metode_allowance,
                 //         'amount' => str_replace(".", "", $request->total_allowance),
                 //         'amountType' => 'CREDIT',
                //          'subsidiaryType' => 'EMPLOYEE',
                //          'employeeNo' => $request->employeeNo,
                //          'departmentName' => $nama_department,
                //      ),
                //      2 =>
                //      array (
                //          'accountNo' => $request->akun_perkiraan_cash,
                //          'amount' => str_replace(".", "", $request->total_cash),
                //          'amountType' => 'DEBIT',
                //          'subsidiaryType' => 'EMPLOYEE',
                //          'employeeNo' => $request->employeeNo,
                 //         'departmentName' => $nama_department,
                //      ),
                //      3 =>
                //      array (
                //          'accountNo' => $request->metode_cash,
                //         'amount' => str_replace(".", "", $request->total_cash),
                //          'amountType' => 'CREDIT',
                //          'subsidiaryType' => 'EMPLOYEE',
                //          'employeeNo' => $request->employeeNo,
                //          'departmentName' => $nama_department,
                //      ),
                //
                //      ),
                //      'transDate' => date('d/m/Y'),
                //      'description' => $data->no_reimbursement." - ".$data->remark." (CASH)",
                //);
                $postData =  json_encode($payload,JSON_UNESCAPED_SLASHES);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_ENCODING , 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                  "Content-Type: application/json",
                  'header' => "Authorization: Bearer ".$token['0'],
                  'X-Session-ID: '.$session['0']));
                $result = curl_exec($ch);
                curl_close($ch);

            } else if($cek_type==3) {
                if($request->total != 0) {
                  
                  $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
                  $payload = array (
                    'detailJournalVoucher' =>
                        array (
                        0 =>
                        array (
                            'accountNo' => $request->akun_perkiraan,
                            'amount' => str_replace(".", "", $request->total),
                            'amountType' => 'DEBIT',
                            'subsidiaryType' => 'EMPLOYEE',
                            'employeeNo' => $request->employeeNo,
                            'departmentName' => $nama_department,
                        ),
                        1 =>
                            array (
                            'accountNo' => $request->metode_cash,
                            'amount' => str_replace(".", "", $request->total),
                            'amountType' => 'CREDIT',
                            'subsidiaryType' => 'EMPLOYEE',
                            'employeeNo' => $request->employeeNo,
                            'departmentName' => $nama_department,
                        ),
                        ),
                        'transDate' => date('d/m/Y'),
                        'description' => $data->no_reimbursement." - ".$data->remark." (CASH)",
                  );
                  $postData =  json_encode($payload,JSON_UNESCAPED_SLASHES);
                  $ch = curl_init($url);
                  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                  curl_setopt($ch, CURLOPT_POST, 1);
                  curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                  curl_setopt($ch, CURLOPT_ENCODING , 1);
                  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    'header' => "Authorization: Bearer ".$token['0'],
                    'X-Session-ID: '.$session['0']));
                  $result = curl_exec($ch);
                  curl_close($ch);
              }

            } else {

                $total_cash = str_replace(".", "", $request->total_cash);
                $total_fleet = str_replace(".", "", $request->total_fleet);

                $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
                
                $detailJournalVoucher = [];

                if ($total_cash > 0) {
                    $detailJournalVoucher[] = [
                        'accountNo' => $request->akun_perkiraan,
                        'amount' => $total_cash,
                        'amountType' => 'DEBIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];

                    $detailJournalVoucher[] = [
                        'accountNo' => $request->metode_cash,
                        'amount' => $total_cash,
                        'amountType' => 'CREDIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];
                }

                if ($total_fleet > 0) {
                    $detailJournalVoucher[] = [
                        'accountNo' => $request->akun_perkiraan_fleet,
                        'amount' => $total_fleet,
                        'amountType' => 'DEBIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];

                    $detailJournalVoucher[] = [
                        'accountNo' => $request->metode_fleet,
                        'amount' => $total_fleet,
                        'amountType' => 'CREDIT',
                        'subsidiaryType' => 'EMPLOYEE',
                        'employeeNo' => $request->employeeNo,
                        'departmentName' => $nama_department,
                    ];
                }

                $payload = [
                    'detailJournalVoucher' => $detailJournalVoucher,
                    'transDate' => date('d/m/Y'),
                    'description' => $data->no_reimbursement . " - " . $data->remark,
                ];

              
                $postData =  json_encode($payload,JSON_UNESCAPED_SLASHES);
                
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_ENCODING , 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                  "Content-Type: application/json",
                  'header' => "Authorization: Bearer ".$token['0'],
                  'X-Session-ID: '.$session['0']));
                $result = curl_exec($ch);
                curl_close($ch);
                
              	
            }

            if($cek_type==1) {

                $data->update([
                    'status' => 5,
                    'tgl_pencairan' => date('Y-m-d H:i:s'),
                    'metode_allowance' => $request->metode_allowance,
                    'metode_cash' => $request->metode_cash,
                    'sumber' => $request->sumber,
                    'penerima' => $request->penerima,
                    'bank' => $request->bank,
                    'no_rek' => $request->no_rek,
                    'akun_perkiraan' => $request->akun_perkiraan,
                    'metode_fleet' => $request->metode_fleet,
                    'akun_perkiraan_fleet' => $request->akun_perkiraan_fleet,
                    'penerima_fleet' => $request->penerima_fleet,
                    'bank_fleet' => $request->bank_fleet,
                    'no_rek_fleet' => $request->no_rek_fleet,
                    'total_fleet' => str_replace(".", "", $request->total_fleet),
                    'total_cash' => str_replace(".", "", $request->total_cash)
                ]);

            } else {

                $data->update([
                    'status' => 5,
                    'tgl_pencairan' => date('Y-m-d H:i:s'),
                    'metode_allowance' => $request->metode_allowance,
                    'metode_cash' => $request->metode_cash,
                    'sumber' => $request->sumber,
                    'penerima' => $request->penerima,
                    'bank' => $request->bank,
                    'no_rek' => $request->no_rek,
                    'akun_perkiraan' => $request->akun_perkiraan,
                ]);

            }

            $nama_approval = ucfirst(auth()->user()->name);

            if($cek_type==1) {
                $direct = "/reimbursement-driver/";
            } else if($cek_type==2) {
                $direct = "/reimbursement-travel/";
            } else {
                $direct = "/reimbursement-entertainment/";
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
                        "* telah dicairkan oleh *".$nama_approval." (FINANCE)*.\n\nTerima kasih.
                        \n\nKlik untuk melihat detail pengajuan : " .
                        url(''.$direct.'' . $data->id),
                ])->post();

            DB::commit();
        return redirect()->back()->with(['success' => 'Data Berhasil Dicairkan']);

        } catch (\Throwable $th) {
            DB::rollback();

            return redirect()->back()->withErrors([$th->getMessage()]);            
        }
       

    }

    
    public function exportSettlement(Request $request)
    {
        $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                    ->leftJoin('users', 'reimbursement.id_user', 'users.id')
                    ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan', 'users.name')
                    ->whereIn('status', [3, 5])
                    ->orderBy('reimbursement.created_at', 'desc');

        if (isset($request->start) && $request->start != "") {
            $data = $data->whereDate('reimbursement.created_at', '>=', $request->start);
        }

        if (isset($request->end) && $request->end != "") {
            $data = $data->whereDate('reimbursement.created_at', '<=', $request->end);
        }

        if (isset($request->status) && $request->status != "" && $request->status != "ALL") {
            $data = $data->where('reimbursement.status', $request->status);
        }

        if (isset($request->user_id) && $request->user_id != "") {
            $data = $data->where('reimbursement.id_user', '=', $request->user_id);
        }

        if (isset($request->type) && $request->type != "") {
            $data = $data->where('reimbursement.reimbursement_type', '=', $request->type);
        }

        $data = $data->orderBy('reimbursement.id', 'DESC')->get();

        $data->loadMissing([
            'user',
            'department',
            'entertaiments',
            'drivers',
            'travels.tripType',
            'travels.hotelCondition',
            'travels.details.costType',
            'rates',
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $applyTableStyle = function ($sheet, $startCol, $startRow, $endCol, $endRow, $headerRow = null) {
            $range = $startCol . $startRow . ':' . $endCol . $endRow;
            $sheet->getStyle($range)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '666666'],
                    ],
                ],
            ]);

            if ($headerRow !== null) {
                $headerRange = $startCol . $headerRow . ':' . $endCol . $headerRow;
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3'],
                    ],
                ]);
            }
        };

        $periodLine = (!empty($request->start) && !empty($request->end))
            ? $request->start . ' - ' . $request->end
            : '-';
        $exportedAt = now()->format('Y-m-d H:i');

        if ($data->isEmpty()) {
            $sheet = $spreadsheet->createSheet(0);
            $sheet->setTitle('Settlement');
            $sheet->setCellValue('A1', 'No data found for selected filter');
        } else {
            foreach ($data as $idx => $row) {
                $sheet = $spreadsheet->createSheet($idx);

                $sheetName = trim((string) ($row->no_reimbursement ?: ('Inquiry ' . ($idx + 1))));
                $sheetName = str_replace(['\\', '/', ':', '?', '*', '[', ']'], '-', $sheetName);
                $sheetName = substr($sheetName, 0, 31);
                if ($sheetName === '') {
                    $sheetName = 'Inquiry ' . ($idx + 1);
                }
                $sheet->setTitle($sheetName);

                $typeName = ((int) $row->reimbursement_type === 1) ? 'DRIVER' : (((int) $row->reimbursement_type === 2) ? 'TRAVEL' : 'ENTERTAINMENT');
                $statusName = ((int) $row->status === 5) ? 'SETTLED' : 'PROCESS SETTLEMENT';
                $nik = optional($row->user)->nikNo ?: (optional($row->user)->idKaryawan ?: '-');

                $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

                if ((int) $row->status === 5) {
                    $sheet->mergeCells('A1:C3');
                    $sheet->setCellValue('A1', 'PAID');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 46,
                            'color' => ['rgb' => 'E10600'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                $sheet->mergeCells('A4:H4');
                $sheet->mergeCells('A5:H5');
                $sheet->mergeCells('A6:H6');
                $sheet->setCellValue('A4', 'PT. SUMITOMO FORESTRY INDONESIA');
                $sheet->setCellValue('A5', 'Settlement Reimbursement UUDP - Laporan Detail (Pencairan)');
                $sheet->setCellValue('A6', 'Periode filter: ' . $periodLine . ' | Diekspor: ' . $exportedAt . ' | No. Inquiry: ' . $row->no_reimbursement);
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1B5E20');
                $sheet->getStyle('A5')->getFont()->setBold(true);
                $sheet->getStyle('A6')->getFont()->setSize(9)->getColor()->setRGB('37474F');

                $sheet->mergeCells('A7:H7');
                $sheet->setCellValue('A7', $typeName . ' Reimbursement');
                $sheet->getStyle('A7')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F5E9'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->setCellValue('A8', 'INQUIRY NO');
                $sheet->mergeCells('B8:D8');
                $sheet->setCellValue('B8', $row->no_reimbursement ?: '-');
                $sheet->setCellValue('E8', 'APPLY DATE');
                $sheet->mergeCells('F8:H8');
                $sheet->setCellValue('F8', optional($row->created_at)->format('Y-m-d') ?: '-');

                $sheet->setCellValue('A9', 'INQUIRY BY');
                $sheet->mergeCells('B9:D9');
                $sheet->setCellValue('B9', optional($row->user)->name ?: '-');
                $sheet->setCellValue('E9', 'TRANSACTION DATE');
                $sheet->mergeCells('F9:H9');
                $sheet->setCellValue('F9', $row->date ?: '-');

                $sheet->setCellValue('A10', 'NIK');
                $sheet->mergeCells('B10:D10');
                $sheet->setCellValue('B10', $nik);
                $sheet->setCellValue('E10', 'STATUS');
                $sheet->mergeCells('F10:H10');
                $sheet->setCellValue('F10', $statusName);

                $sheet->setCellValue('A11', 'DEPARTMENT');
                $sheet->mergeCells('B11:D11');
                $sheet->setCellValue('B11', optional($row->department)->nama_departemen ?: '-');
                $sheet->setCellValue('E11', 'TYPE');
                $sheet->mergeCells('F11:H11');
                $sheet->setCellValue('F11', $typeName);

                $sheet->setCellValue('A12', 'REMARK (HEADER)');
                $sheet->mergeCells('B12:H12');
                $sheet->setCellValue('B12', $row->remark ?: '-');

                $sheet->setCellValue('A13', 'TOTAL INQUIRY');
                $sheet->mergeCells('B13:H13');
                $sheet->setCellValue('B13', (float) $row->nominal_pengajuan);
                $sheet->getStyle('B13')->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('B13')->getFont()->setBold(true);

                $sheet->getStyle('A8:A13')->getFont()->setBold(true);
                $sheet->getStyle('E8:E11')->getFont()->setBold(true);
                $applyTableStyle($sheet, 'A', 8, 'H', 13);

                $r = 15;

                if ((int) $row->reimbursement_type === 1) {
                    $groups = $row->drivers->groupBy(function ($line) {
                        $payment = strtoupper(trim((string) ($line->payment_type ?? '')));
                        return $payment !== '' ? $payment : 'UNKNOWN';
                    });

                    if ($groups->isEmpty()) {
                        $groups = collect(['UNKNOWN' => collect()]);
                    }

                    foreach ($groups as $paymentType => $lines) {
                        $sheet->mergeCells('A' . $r . ':H' . $r);
                        $sheet->setCellValue('A' . $r, 'Payment Type: ' . $paymentType);
                        $sheet->getStyle('A' . $r)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                        ]);
                        $r++;

                        $sheet->fromArray(['No', 'Toll', 'Parking', 'Gasolin', 'Other', 'Payment', 'Amount', 'Remark (Detail)'], null, 'A' . $r);
                        $headerRow = $r;
                        $r++;
                        $no = 1;
                        $total = 0;

                        foreach ($lines as $line) {
                            $subtotal = (float) ($line->subtotal ?? 0);
                            $total += $subtotal;
                            $sheet->fromArray([
                                $no++,
                                (float) ($line->toll ?? 0),
                                (float) ($line->parking ?? 0),
                                (float) ($line->gasoline ?? 0),
                                (float) ($line->others ?? 0),
                                $line->payment_type ?? '',
                                $subtotal,
                                $line->remark ?? '',
                            ], null, 'A' . $r);
                            $r++;
                        }

                        $sheet->setCellValue('A' . $r, 'Total ' . $paymentType);
                        $sheet->mergeCells('A' . $r . ':F' . $r);
                        $sheet->setCellValue('G' . $r, $total);
                        $sheet->setCellValue('H' . $r, '');
                        $sheet->getStyle('A' . $r . ':H' . $r)->getFont()->setBold(true);

                        $applyTableStyle($sheet, 'A', $headerRow, 'H', $r, $headerRow);
                        $sheet->getStyle('B' . ($headerRow + 1) . ':G' . $r)->getNumberFormat()->setFormatCode('#,##0');
                        $r += 2;
                    }
                } elseif ((int) $row->reimbursement_type === 3) {
                    $groups = $row->entertaiments->groupBy(function ($line) {
                        $payment = strtoupper(trim((string) ($line->payment_type ?? '')));
                        return $payment !== '' ? $payment : 'UNKNOWN';
                    });

                    if ($groups->isEmpty()) {
                        $groups = collect(['UNKNOWN' => collect()]);
                    }

                    foreach ($groups as $paymentType => $lines) {
                        $sheet->mergeCells('A' . $r . ':H' . $r);
                        $sheet->setCellValue('A' . $r, 'Payment Type: ' . $paymentType);
                        $sheet->getStyle('A' . $r)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                        ]);
                        $r++;

                        $sheet->fromArray(['Date', 'Attendance', 'Position', 'Place', 'Guest', 'Payment', 'Amount', 'Remark (Detail)'], null, 'A' . $r);
                        $headerRow = $r;
                        $r++;
                        $total = 0;

                        foreach ($lines as $line) {
                            $amount = (float) ($line->amount ?? 0);
                            $total += $amount;
                            $sheet->fromArray([
                                $line->date ?? '',
                                $line->attendance ?? '',
                                $line->position ?? '',
                                $line->place ?? '',
                                $line->guest ?? '',
                                $line->payment_type ?? '',
                                $amount,
                                $line->remark ?? '',
                            ], null, 'A' . $r);
                            $r++;
                        }

                        $sheet->setCellValue('A' . $r, 'Total ' . $paymentType);
                        $sheet->mergeCells('A' . $r . ':F' . $r);
                        $sheet->setCellValue('G' . $r, $total);
                        $sheet->setCellValue('H' . $r, '');
                        $sheet->getStyle('A' . $r . ':H' . $r)->getFont()->setBold(true);

                        $applyTableStyle($sheet, 'A', $headerRow, 'H', $r, $headerRow);
                        $sheet->getStyle('G' . ($headerRow + 1) . ':G' . $r)->getNumberFormat()->setFormatCode('#,##0');
                        $r += 2;
                    }
                } else {
                    $groups = collect();
                    foreach ($row->travels as $travel) {
                        foreach ($travel->details as $detail) {
                            $payment = strtoupper(trim((string) ($detail->payment_type ?? '')));
                            if ($payment === '') {
                                $payment = 'UNKNOWN';
                            }
                            if (!$groups->has($payment)) {
                                $groups->put($payment, collect());
                            }
                            $groups[$payment]->push(['travel' => $travel, 'detail' => $detail]);
                        }
                    }

                    if ($groups->isEmpty()) {
                        $groups = collect(['UNKNOWN' => collect()]);
                    }

                    foreach ($groups as $paymentType => $lines) {
                        $sheet->mergeCells('A' . $r . ':I' . $r);
                        $sheet->setCellValue('A' . $r, 'Payment Type: ' . $paymentType);
                        $sheet->getStyle('A' . $r)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                        ]);
                        $r++;

                        $sheet->fromArray(['Trip Date', 'Trip Type', 'Purpose', 'Cost Type', 'Destination', 'Currency', 'Amount (IDR)', 'Payment', 'Remark (Detail)'], null, 'A' . $r);
                        $headerRow = $r;
                        $r++;
                        $total = 0;

                        foreach ($lines as $pair) {
                            $travel = $pair['travel'];
                            $detail = $pair['detail'];
                            $idr = (float) ($detail->idr_rate ?? 0);
                            $total += $idr;
                            $sheet->fromArray([
                                $travel->date ?? $row->date ?? '',
                                optional($travel->tripType)->name ?? '',
                                $travel->purpose ?? '',
                                optional($detail->costType)->name ?? '',
                                $detail->destination ?? '',
                                $detail->currency ?? '',
                                $idr,
                                $detail->payment_type ?? '',
                                $detail->remarks ?? '',
                            ], null, 'A' . $r);
                            $r++;
                        }

                        $sheet->setCellValue('A' . $r, 'Total ' . $paymentType);
                        $sheet->mergeCells('A' . $r . ':F' . $r);
                        $sheet->setCellValue('G' . $r, $total);
                        $sheet->mergeCells('H' . $r . ':I' . $r);
                        $sheet->getStyle('A' . $r . ':I' . $r)->getFont()->setBold(true);

                        $applyTableStyle($sheet, 'A', $headerRow, 'I', $r, $headerRow);
                        $sheet->getStyle('G' . ($headerRow + 1) . ':G' . $r)->getNumberFormat()->setFormatCode('#,##0');
                        $r += 2;
                    }
                }

                $r += 1;
                $sheet->mergeCells('A' . $r . ':C' . $r);
                $sheet->mergeCells('D' . $r . ':F' . $r);
                $sheet->mergeCells('G' . $r . ':H' . $r);
                $sheet->setCellValue('A' . $r, 'Head Department');
                $sheet->setCellValue('D' . $r, 'HR GA');
                $sheet->setCellValue('G' . $r, 'Finance');
                $sheet->getStyle('A' . $r . ':H' . $r)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $r++;
                $sheet->getRowDimension($r)->setRowHeight(30);
                $r++;
                $sheet->mergeCells('A' . $r . ':C' . $r);
                $sheet->mergeCells('D' . $r . ':F' . $r);
                $sheet->mergeCells('G' . $r . ':H' . $r);
                $sheet->setCellValue('A' . $r, strtoupper((string) ($row->mengetahui_op ?? '-')));
                $sheet->setCellValue('D' . $r, strtoupper((string) ($row->mengetahui_finance ?? '-')));
                $sheet->setCellValue('G' . $r, strtoupper((string) ($row->mengetahui_owner ?? '-')));
                $sheet->getStyle('A' . $r . ':H' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $applyTableStyle($sheet, 'A', $r - 2, 'H', $r);

                foreach (range('A', 'I') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            @ini_set('display_errors', '0');
            @ini_set('html_errors', '0');
            @error_reporting(E_ERROR | E_PARSE);

            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, 'Export_Settlement_Detail.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);


    }


}
