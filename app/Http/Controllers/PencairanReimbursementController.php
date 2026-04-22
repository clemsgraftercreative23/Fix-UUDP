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
use DB;
class PencairanReimbursementController extends Controller
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
            $accuratePayload = $this->buildAccuratePayloadForSettlement($data, $request, $nama_department);

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
                    'total_cash' => str_replace(".", "", $request->total_cash),
                    'accurate_payload_json' => $accuratePayload ? json_encode($accuratePayload, JSON_UNESCAPED_SLASHES) : null,
                    'accurate_synced_at' => null,
                    'accurate_sync_status' => $accuratePayload ? 'pending' : null,
                    'accurate_sync_message' => null,
                ]);

            } else {
                $updatePayload = [
                    'status' => 5,
                    'tgl_pencairan' => date('Y-m-d H:i:s'),
                    'metode_allowance' => $request->metode_allowance,
                    'metode_cash' => $request->metode_cash,
                    'sumber' => $request->sumber,
                    'penerima' => $request->penerima,
                    'bank' => $request->bank,
                    'no_rek' => $request->no_rek,
                    'akun_perkiraan' => $request->akun_perkiraan,
                    'accurate_payload_json' => $accuratePayload ? json_encode($accuratePayload, JSON_UNESCAPED_SLASHES) : null,
                    'accurate_synced_at' => null,
                    'accurate_sync_status' => $accuratePayload ? 'pending' : null,
                    'accurate_sync_message' => null,
                ];

                if ($cek_type == 2) {
                    $updatePayload['metode_bdc'] = $request->metode_bdc;
                }

                $data->update($updatePayload);

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

    public function syncAccurate($id)
    {
        $data = Reimbursement::findOrFail($id);

        if (auth()->user()->jabatan !== 'Owner') {
            return redirect()->back()->withErrors(['Hanya Owner yang dapat melakukan sync Accurate.']);
        }

        if ((int) $data->status !== 5) {
            return redirect()->back()->withErrors(['Reimbursement belum berstatus SETTLED.']);
        }

        if (!empty($data->accurate_synced_at)) {
            return redirect()->back()->withErrors(['Data ini sudah tersinkron ke Accurate.']);
        }

        $payload = json_decode($data->accurate_payload_json ?? '', true);
        if (!is_array($payload) || empty($payload['detailJournalVoucher'])) {
            return redirect()->back()->withErrors(['Payload Accurate belum tersedia. Silakan lakukan settlement ulang terlebih dahulu.']);
        }

        try {
            $syncResult = $this->postAccurateJournal($payload);

            if (!($syncResult['success'] ?? false)) {
                $message = $syncResult['message'] ?? 'Sync ke Accurate gagal.';
                $data->update([
                    'accurate_sync_status' => 'failed',
                    'accurate_sync_message' => $message,
                ]);
                return redirect()->back()->withErrors([$message]);
            }

            $data->update([
                'accurate_synced_at' => date('Y-m-d H:i:s'),
                'accurate_sync_status' => 'synced',
                'accurate_sync_message' => null,
            ]);

            return redirect()->back()->with(['success' => 'Sinkronisasi Accurate berhasil.']);
        } catch (\Throwable $th) {
            $data->update([
                'accurate_sync_status' => 'failed',
                'accurate_sync_message' => $th->getMessage(),
            ]);

            return redirect()->back()->withErrors([$th->getMessage()]);
        }
    }

    private function normalizeMoney($value)
    {
        if ($value === null) {
            return 0;
        }

        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function buildAccuratePayloadForSettlement(Reimbursement $data, Request $request, $nama_department)
    {
        $detailJournalVoucher = [];
        $description = $data->no_reimbursement . " - " . $data->remark;
        $reimbursementType = (int) $data->reimbursement_type;

        if ($reimbursementType === 2) {
            $description .= " (SETTLEMENT TRAVEL)";
            $detailJournalVoucher = $this->buildTravelJournalVoucherLines($request, $nama_department);
        } elseif ($reimbursementType === 3) {
            $total = $this->normalizeMoney($request->total);
            if ($total > 0) {
                $detailJournalVoucher[] = [
                    'accountNo' => $request->akun_perkiraan,
                    'amount' => $total,
                    'amountType' => 'DEBIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
                $detailJournalVoucher[] = [
                    'accountNo' => $request->metode_cash,
                    'amount' => $total,
                    'amountType' => 'CREDIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
            }
        } else {
            $totalCash = $this->normalizeMoney($request->total_cash);
            $totalFleet = $this->normalizeMoney($request->total_fleet);
            if ($totalCash > 0) {
                $detailJournalVoucher[] = [
                    'accountNo' => $request->akun_perkiraan,
                    'amount' => $totalCash,
                    'amountType' => 'DEBIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
                $detailJournalVoucher[] = [
                    'accountNo' => $request->metode_cash,
                    'amount' => $totalCash,
                    'amountType' => 'CREDIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
            }
            if ($totalFleet > 0) {
                $detailJournalVoucher[] = [
                    'accountNo' => $request->akun_perkiraan_fleet,
                    'amount' => $totalFleet,
                    'amountType' => 'DEBIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
                $detailJournalVoucher[] = [
                    'accountNo' => $request->metode_fleet,
                    'amount' => $totalFleet,
                    'amountType' => 'CREDIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
            }
        }

        if (empty($detailJournalVoucher)) {
            return null;
        }

        return [
            'detailJournalVoucher' => $detailJournalVoucher,
            'transDate' => date('d/m/Y'),
            'description' => $description,
        ];
    }

    private function buildTravelJournalVoucherLines(Request $request, $nama_department)
    {
        $detailJournalVoucher = [];
        $breakdownEntries = (array) $request->input('breakdown_entries', []);
        $groupTotals = [
            'BDC' => 0,
            'ALLOWANCE' => 0,
            'CASH' => 0,
        ];

        if (!empty($breakdownEntries)) {
            foreach ($breakdownEntries as $entry) {
                $group = strtoupper(trim((string) ($entry['group'] ?? '')));
                $amount = (int) ($entry['amount'] ?? 0);
                $accountNo = trim((string) ($entry['account_no'] ?? ''));

                if ($amount <= 0 || !array_key_exists($group, $groupTotals)) {
                    continue;
                }

                if ($accountNo === '') {
                    throw new \Exception('Akun perkiraan per cost type wajib diisi.');
                }

                $groupTotals[$group] += $amount;
                $detailJournalVoucher[] = [
                    'accountNo' => $accountNo,
                    'amount' => $amount,
                    'amountType' => 'DEBIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
            }

            $creditAccounts = [
                'BDC' => trim((string) $request->metode_bdc),
                'ALLOWANCE' => trim((string) $request->metode_allowance),
                'CASH' => trim((string) $request->metode_cash),
            ];

            foreach ($groupTotals as $group => $totalAmount) {
                if ($totalAmount <= 0) {
                    continue;
                }

                if (empty($creditAccounts[$group])) {
                    throw new \Exception('Settlement method untuk ' . $group . ' wajib diisi.');
                }

                $detailJournalVoucher[] = [
                    'accountNo' => $creditAccounts[$group],
                    'amount' => $totalAmount,
                    'amountType' => 'CREDIT',
                    'subsidiaryType' => 'EMPLOYEE',
                    'employeeNo' => $request->employeeNo,
                    'departmentName' => $nama_department,
                ];
            }

            return $detailJournalVoucher;
        }

        // Backward compatibility for old form payload.
        $totalAllowance = $this->normalizeMoney($request->total_allowance);
        $totalCash = $this->normalizeMoney($request->total_cash);
        if ($totalAllowance > 0) {
            $detailJournalVoucher[] = [
                'accountNo' => $request->akun_perkiraan_allowance,
                'amount' => $totalAllowance,
                'amountType' => 'DEBIT',
                'subsidiaryType' => 'EMPLOYEE',
                'employeeNo' => $request->employeeNo,
                'departmentName' => $nama_department,
            ];
            $detailJournalVoucher[] = [
                'accountNo' => $request->metode_allowance,
                'amount' => $totalAllowance,
                'amountType' => 'CREDIT',
                'subsidiaryType' => 'EMPLOYEE',
                'employeeNo' => $request->employeeNo,
                'departmentName' => $nama_department,
            ];
        }
        if ($totalCash > 0) {
            $detailJournalVoucher[] = [
                'accountNo' => $request->akun_perkiraan_cash,
                'amount' => $totalCash,
                'amountType' => 'DEBIT',
                'subsidiaryType' => 'EMPLOYEE',
                'employeeNo' => $request->employeeNo,
                'departmentName' => $nama_department,
            ];
            $detailJournalVoucher[] = [
                'accountNo' => $request->metode_cash,
                'amount' => $totalCash,
                'amountType' => 'CREDIT',
                'subsidiaryType' => 'EMPLOYEE',
                'employeeNo' => $request->employeeNo,
                'departmentName' => $nama_department,
            ];
        }

        return $detailJournalVoucher;
    }

    private function postAccurateJournal(array $payload)
    {
        $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
        $token = Api::where('id', 1)->get()->pluck('token');
        $session = Api::where('id', 1)->get()->pluck('session');

        $postData = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'header' => "Authorization: Bearer " . $token['0'],
            'X-Session-ID: ' . $session['0']
        ));
        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result === false || !empty($curlError)) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Accurate: ' . $curlError,
            ];
        }

        $decoded = json_decode($result, true);
        if (is_array($decoded)) {
            if ((array_key_exists('success', $decoded) && !$decoded['success']) || (array_key_exists('s', $decoded) && !$decoded['s'])) {
                $message = $decoded['d'] ?? $decoded['message'] ?? 'Accurate menolak payload.';
                return [
                    'success' => false,
                    'message' => is_string($message) ? $message : 'Accurate menolak payload.',
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'ok',
            'raw' => $result,
        ];
    }

    
    public function exportSettlement(Request $request)
    {
        $data = Reimbursement::leftJoin('master_project', 'reimbursement.id_project', 'master_project.id')
                    ->leftJoin('users', 'reimbursement.id_user', 'users.id')
                    ->select('reimbursement.*', 'master_project.nama', 'master_project.no_project', 'master_project.keterangan', 'users.name')
                    ->whereIn('status', [3, 5])
                    ->where('reimbursement.reimbursement_type', 3)
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Settlement');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

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
                $headerRange = $startCol . $headerRow . ':' . $endCol . $headerRow;                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        };

        $periodLine = (!empty($request->start) && !empty($request->end))
            ? $request->start . ' - ' . $request->end
            : '-';
        $exportedAt = now()->format('Y-m-d H:i');

        $sheet->setCellValue('A1', 'Settlement Reimbursement UUDP - Data Export');
        $sheet->setCellValue('A2', 'Periode filter: ' . $periodLine . ' | Diekspor: ' . $exportedAt);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('4E5D6C');

        $headerRow = 4;
        $headers = [
            'No', 'Inquiry No', 'Apply Date', 'Inquiry By', 'Department', 'Status', 'Transaction Date',
            'Payment Type', 'Detail Date', 'Attendance', 'Position', 'Place', 'Guest', 'Amount',
            'Remark (Header)', 'Remark (Detail)', 'Watermark'
        ];
        $sheet->fromArray($headers, null, 'A' . $headerRow);

        $mapStatus = function ($status) {
            return ((int) $status === 5) ? 'SETTLED' : 'PROCESS SETTLEMENT';
        };

        $r = $headerRow + 1;
        $no = 1;

        foreach ($data as $row) {
            $base = [
                $no,
                $row->no_reimbursement ?: '-',
                optional($row->created_at)->format('Y-m-d') ?: '-',
                optional($row->user)->name ?: '-',
                optional($row->department)->nama_departemen ?: '-',
                $mapStatus($row->status),
                $row->date ?: '-',
            ];
            $paidMark = ((int) $row->status === 5) ? 'PAID' : '';

            if ($row->entertaiments->isEmpty()) {
                $sheet->fromArray(array_merge($base, ['', '', '', '', '', '', '', (float) $row->nominal_pengajuan, $row->remark ?: '', '', $paidMark]), null, 'A' . $r);
                $r++;
            } else {
                foreach ($row->entertaiments as $line) {
                    $sheet->fromArray(array_merge($base, [
                        $line->payment_type ?: '',
                        $line->date ?: '',
                        $line->attendance ?: '',
                        $line->position ?: '',
                        $line->place ?: '',
                        $line->guest ?: '',
                        (float) ($line->amount ?? 0),
                        $row->remark ?: '',
                        $line->remark ?: '',
                        $paidMark,
                    ]), null, 'A' . $r);
                    $r++;
                }
            }

            $no++;
        }

        if ($data->isEmpty()) {
            $sheet->setCellValue('A5', 'No data found for selected filter');
        }

        $endRow = max($headerRow, $r - 1);
        $applyTableStyle($sheet, 'A', $headerRow, 'Q', $endRow, $headerRow);
        if ($endRow > $headerRow) {
            $sheet->getStyle('N' . ($headerRow + 1) . ':N' . $endRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('Q' . ($headerRow + 1) . ':Q' . $endRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'C62828'],
                ],
            ]);
        }

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
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





