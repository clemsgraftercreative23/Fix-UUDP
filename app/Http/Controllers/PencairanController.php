<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use DataTables;
use App\Pengajuan;
use App\Pencairan;
use App\User;
use App\Listpengajuan;
use App\Master_project;
use App\Detail_pencairan;
use App\Api;
use App\Notif_push;
use Auth;
use Validator;
// use \Yajra\Datatables\Datatables;

class PencairanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function __construct()
     {
         $this->middleware('auth');
     }
    public function index(Request $request)
      {
        $project = DB::table('master_project')->get();
        $kelompok = DB::table('master_kelompok_kegiatan')->get();
        $daftar = DB::table('master_daftar_rencana')->get();

          if(request()->ajax())
          {

            $jabatan = Auth::user()->jabatan;
            if($jabatan == 'superadmin' or $jabatan == 'Owner'){
            $data = DB::table('pengajuan')
            ->join('master_project','pengajuan.id_project','master_project.id')
            ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
            ->orderBy('pengajuan.id','DESC')
            ->where('pengajuan.mengetahui', '!=', null)
            ->where('pengajuan.menyetujui', '!=', null);
            if(!empty($request->first) && !empty($request->last))
                         {
                             $first = $request->first;
                             $last = $request->last;
                             $tahun = date("Y");
                             $from = $tahun.'-'.$first.'-01';
                             $to = $tahun.'-'.$last.'-30';

                          $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                         }

            $data = $data->get();
           return datatables()->of($data)
           ->addColumn('action', function ($data) {
             if($data->sisa_pengajuan != null){
              $cek_notifpush = DB::table('notif_push')->where('id_pengajuan',$data->id)->where('jabatan','Owner')->where('read_at','')->count();
              if($cek_notifpush>0) {
              $showicon =  '<i class="fa fa-info-circle" style="color:red"></i>';
              } else {
              $showicon= '';
              }
             $button = '<button name="owner" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="owner btn btn-success  btn-sm">Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'&nbsp;'.$showicon.'</button>';

           }else {
              $cek_notifpush = DB::table('notif_push')->where('id_pengajuan',$data->id)->where('jabatan','Owner')->where('read_at','')->count();
              if($cek_notifpush>0) {
              $showicon =  '<i class="fa fa-info-circle" style="color:red"></i>';
              } else {
              $showicon= '';
              }
             $button = '<button name="owner" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="owner btn btn-secondary btn-sm">Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'&nbsp;'.$showicon.'</button>';
           }
             $button .= '&nbsp;&nbsp;';

             return $button;

           })
           ->addColumn('totalpengajuan', function ($data) {
             if($data->sisa_pengajuan != 0){
             $button = '<label style="font-size:15px;"> Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label><br><span style="font-size:10px;"> Sisa Pengajuan: Rp '.number_format($data->sisa_pengajuan,0, ',' , '.').'</span>';
           }else {
             $button = '<label> Rp. '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label>';
           }
             $button .= '&nbsp;&nbsp;';

             return $button;

           })
           ->rawColumns(['action','totalpengajuan'])
           ->make(true);
        }elseif($jabatan == 'Finance'){
          $id_user = Auth::user()->id;
          $data = DB::table('pengajuan')
          ->join('master_project','pengajuan.id_project','master_project.id')
          ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
          ->orderBy('pengajuan.id','DESC')
          ->where('pengajuan.mengetahui', '!=', null)
          ->where('pengajuan.menyetujui', '!=', null);
          if(!empty($request->first) && !empty($request->last))
                       {
                           $first = $request->first;
                           $last = $request->last;
                           $tahun = date("Y");
                           $from = $tahun.'-'.$first.'-01';
                           $to = $tahun.'-'.$last.'-30';

                        $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                       }

                       return datatables()->of($data)
                       ->addColumn('action', function ($data) {
                         if($data->sisa_pengajuan != null){
                          $cek_notifpush = DB::table('notif_push')->where('id_pengajuan',$data->id)->where('jabatan','Finance')->where('read_at','')->count();
                         if($cek_notifpush>0) {
                            $showicon =  '<i class="fa fa-info-circle" style="color:red"></i>';
                          } else {
                            $showicon= '';
                          }
                         $button = '<button name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses btn btn-success  btn-sm">Rp  '.number_format($data->nominal_pengajuan,0, ',' , '.').'&nbsp;'.$showicon.'</button>';

                       }else {
                         $cek_notifpush = DB::table('notif_push')->where('id_pengajuan',$data->id)->where('jabatan','Finance')->where('read_at','')->count();
                         if($cek_notifpush>0) {
                            $showicon =  '<i class="fa fa-info-circle" style="color:red"></i>';
                          } else {
                            $showicon= '';
                          }
                         $button = '</i><button name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses btn btn-secondary btn-sm">
                           Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'&nbsp;'.$showicon.'</button>';
                       }
                         $button .= '&nbsp;&nbsp;';

                         return $button;

                       })
                       ->addColumn('totalpengajuan', function ($data) {
                         if($data->sisa_pengajuan != 0){
                         $button = '<label style="font-size:15px;"> Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label><br><span style="font-size:10px;"> Sisa Pengajuan: Rp '.number_format($data->sisa_pengajuan,0, ',' , '.').'</span>';
                       }else {
                         $button = '<label> Rp. '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label>';
                       }
                         $button .= '&nbsp;&nbsp;';

                         return $button;

                       })
                       ->rawColumns(['action','totalpengajuan'])
                       ->make(true);
        }else {
          $id_user = Auth::user()->id;
          $data = DB::table('pengajuan')
          ->join('master_project','pengajuan.id_project','master_project.id')
          ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
          ->orderBy('pengajuan.id','DESC')
          ->where('pengajuan.mengetahui', '!=', null)
          ->where('pengajuan.id_user', $id_user)
          ->where('pengajuan.menyetujui', '!=', null);
          if(!empty($request->first) && !empty($request->last))
                       {
                           $first = $request->first;
                           $last = $request->last;
                           $tahun = date("Y");
                           $from = $tahun.'-'.$first.'-01';
                           $to = $tahun.'-'.$last.'-30';

                        $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                       }

                       return datatables()->of($data)
                       ->addColumn('action', function ($data) {
                         if($data->sisa_pengajuan != null){

                         $button = '<button name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses btn btn-success  btn-sm">Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'</button>';

                       }else {
                         $button = '<button name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses btn btn-secondary btn-sm">Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'</button>';
                       }
                         $button .= '&nbsp;&nbsp;';

                         return $button;

                       })
                       ->addColumn('totalpengajuan', function ($data) {
                         if($data->sisa_pengajuan != 0){
                         $button = '<label style="font-size:15px;"> Rp '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label><br><span style="font-size:10px;"> Sisa Pengajuan: Rp '.number_format($data->sisa_pengajuan,0, ',' , '.').'</span>';
                       }else {
                         $button = '<label> Rp. '.number_format($data->nominal_pengajuan,0, ',' , '.').'</label>';
                       }
                         $button .= '&nbsp;&nbsp;';

                         return $button;

                       })
                       ->rawColumns(['action','totalpengajuan'])
                       ->make(true);
        }

      }
           return view('pencairan.index',['project' => $project, 'kelompok' => $kelompok, 'daftar' => $daftar ]);
      }

      public function totalpencairan(Request $request){
        $data = DB::table('pengajuan')
        ->select( DB::raw('SUM(nominal_pengajuan - sisa_pengajuan) as total'))
        ->whereNotNull('sisa_pengajuan')
        ->where('status',3);
        if(!empty($request->first) && !empty($request->last))
                     {
                         $first = $request->first;
                         $last = $request->last;
                         $tahun = date("Y");
                         $from = $tahun.'-'.$first.'-01';
                         $to = $tahun.'-'.$last.'-30';

                      $data = $data->whereBetween('created_at',[$from,$to]);

                     }
              $data = $data->get();

          $array = array();
          foreach ($data as $key) {
            $array[] = number_format( $key->total,0, ',' , '.');

          }

              return json_encode($array);

      }

      public function cektermin($id){
        $data = DB::table("pencairan")
        ->where("id_pengajuan",$id)
        ->orderBy('id','ASC')
        ->get();
        $cou = DB::table("pencairan")
        ->where("id_pengajuan",$id)
        ->orderBy('id','ASC')
        ->count();
        $sum =  DB::table('pencairan')
        ->where("id_pengajuan",$id)
        ->where("status", 1)
        ->sum('nominal');
        return response()->json(['hasil' => $data, 'cek' => $cou, 'sum' => $sum]);
      }

      public function sisapencairan(Request $request){
        $data = DB::table('pengajuan')
        ->select( DB::raw('SUM(sisa_pengajuan) as total'))
        ->whereNotNull('sisa_pengajuan')
        ->where('status',3);
        if(!empty($request->first) && !empty($request->last))
                     {
                         $first = $request->first;
                         $last = $request->last;
                         $tahun = date("Y");
                         $from = $tahun.'-'.$first.'-01';
                         $to = $tahun.'-'.$last.'-30';

                      $data = $data->whereBetween('created_at',[$from,$to]);

                     }
              $data = $data->get();

          $array = array();
          foreach ($data as $key) {
            $array[] = number_format( $key->total,0, ',' , '.');

          }

              return json_encode($array);

      }


      public function insertPencairan($id) {
         $pengajuan = DB::select( DB::raw("SELECT * FROM pengajuan WHERE no_pengajuan='$id'"));
         $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY id DESC"));
         $qry = DB::table("pengajuan")->where("no_pengajuan",$id)->pluck("id");
         $id_pengajuan = $qry['0'];
         $data = Pengajuan::where('no_pengajuan',$id)->first();
         $karyawan = User::where('id',$data->id_user)->first();
         // $pencairan = DB::select( DB::raw("SELECT *,(SELECT COUNT(*) FROM notif_push WHERE notif_push.id_pencairan = pencairan.id AND jabatan='Finance' AND read_at='') AS jumlah_notif FROM pencairan WHERE pencairan.id_pengajuan='$id_pengajuan'"));
         $pencairan = DB::select( DB::raw("SELECT pencairan.*, (SELECT COUNT(*) FROM notif_push WHERE notif_push.id_pencairan = pencairan.id AND jabatan='Finance' AND read_at='') AS jumlah_notif, (SELECT COUNT(*) FROM notif_push WHERE notif_push.id_pencairan = pencairan.id AND jabatan='Owner' AND read_at='') AS jumlah_notif_owner  FROM pencairan WHERE pencairan.id_pengajuan='$id_pengajuan'"));
         $kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
         return view('pencairan.addPencairan',['pengajuan'=>$pengajuan,'karyawan' => $karyawan,'pencairan'=>$pencairan,'kasbank' => $kasbank,'departemen'=>$departemen]);
      }

      public function edit($id) {
        if(request()->ajax())
        {
            $data = Pencairan::findOrFail($id);
            $id_pengajuan = $data['id_pengajuan'];
            $qry_pengajuan = DB::table("pengajuan")->where("id",$id_pengajuan)->pluck("id_user");
            $qry_project = DB::table("pengajuan")->where("id",$id_pengajuan)->pluck("id_project");
            $id_user = $qry_pengajuan['0'];
            $id_project = $qry_project['0'];
            $user = User::findOrFail($id_user);
            $project = Master_project::findOrFail($id_project);
            $qry_nominal = DB::table("pengajuan")->where("id",$id_pengajuan)->pluck("nominal_pengajuan");
            $pres = $data['nominal'];
            $nilai_trf = number_format( ($pres/100) * $qry_nominal['0'],0, ',' , '.');
            return response()->json(['data' => $data,'user'=>$user,'project'=>$project,'nilai_trf'=>$nilai_trf]);
        }
      }


      public function detailPush($id) {

        $qry_pencairan = DB::table("pencairan")->where("id",$id)->pluck("id_pengajuan");
        $id_pengajuan = $qry_pencairan['0'];
        $qry_notifpush = DB::table("notif_push")->where("id_pengajuan",$id_pengajuan)->where('id_pencairan',$id)->where('read_at','')->pluck("id");
        $id_notif = $qry_notifpush['0'];

        DB::table('notif_push')->where('id_pengajuan', $id_pengajuan)->where('id_pencairan',$id)->update(['read_at' => date('d-m-Y')]);

        if(request()->ajax())
        {
            $data = Notif_push::findOrFail($id_notif);
            return response()->json(['data' => $data]);
        }
      }

      public function getMetode($id) {
          $daftar = DB::table("listkasbank")->where("kode_list",$id)->pluck("nama_list","kode_kasbank");
          return json_encode($daftar);
      }

      public function storePayment(Request $request)
      {
        \DB::beginTransaction();

        try {

            $rules = array(
                'metode'    =>  'required',
                'sumber'    =>  'required',
                'penerima'    =>  'required',
                'bank'    =>  'required',
                'no_rek'    =>  'required',
                'file_bukti'         =>  'required|file',
            );

            $error = Validator::make($request->all(), $rules);

            if($error->fails())
            {
                return response()->json(['errors' => $error->errors()->all()]);
            }

            $image = $request->file('file_bukti');
            $new_name = rand() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/file_bukti'), $new_name);

            $tgl_pencairan = date("Y-m-d", strtotime($request->transDate));
            $nominal = str_replace(".", "", $request->ammount);
            $form_data = array(
                'id_pencairan'        =>  $request->id,
                'metode'        =>  $request->metode,
                'sumber'        =>  $request->sumber,
                'tgl_pencairan'        =>  $tgl_pencairan,
                'penerima'        =>  $request->penerima,
                'bank'        =>  $request->bank,
                'no_rek'        =>  $request->no_rek,
                'nominal'        =>  $nominal,
                'keterangan'        =>  $request->description,
                'created_by'        =>  Auth::user()->username,
                'updated_by'        =>  "-",
                'file_bukti'             =>  $new_name
            );

            Detail_pencairan::create($form_data);

            $form_edit = array(
              'status'             =>  1
            );
            Pencairan::whereId($request->id)->update($form_edit);

            $qry_pencairan = DB::table("pencairan")->where("id",$request->id)->pluck("id_pengajuan");
            $id_pengajuan = $qry_pencairan['0'];
            DB::table('notif_push')->where('id_pengajuan', $id_pengajuan)->where('id_pencairan',$request->id)->update(['read_at' => date('d-m-Y')]);

            $date = date('Y-m-d H:i:s');
            $c = DB::table('pengajuan')
            ->join('pencairan','pengajuan.id','pencairan.id_pengajuan')
            ->where('pencairan.id', $request->id)
            ->pluck('pengajuan.no_pengajuan');
            $ix= Auth::user()->name;
            DB::insert('insert into notif (data, created_at, created_by) values (?, ?, ?)', ['Pengajuan Anda Telah Dicairkan Dengan ID '.$c[0].' Dengan Nominal '.$request->ammount, $date, $ix]);
            /// update total pencairan di tbl pengajuan
            $id = DB::table('pengajuan')
            ->join('pencairan','pengajuan.id','pencairan.id_pengajuan')
            ->where('pencairan.id', $request->id)
            ->pluck('pengajuan.id');

            $cek = DB::table('pencairan')
            ->select( DB::raw('SUM(nominal) as total'))
            ->where('id_pengajuan', $id[0])
            ->where('status', 1)
            ->pluck('total');


            $cok = DB::table('pengajuan')
            ->join('pencairan','pengajuan.id','pencairan.id_pengajuan')
            ->where('pencairan.id', $request->id)
            ->pluck('pengajuan.nominal_pengajuan');


            $a = $cok[0];
            $b = $cek[0];
            $hit = $b/100 * $a;

            $sisa_pengajuan = $a-$hit;

            $form_data = array(
                'sisa_pengajuan'        =>  $sisa_pengajuan
            );
            Pengajuan::whereId($id[0])->update($form_data);

            $dt = Pengajuan::find($id[0]);
            $user = \App\User::where('id',$dt->id_user)->first();
            $curl = \Curl::to('https://api.fonnte.com/send')
                        ->withHeaders([
                            'Authorization: G-BJE9txd#aXDewvme7u'
                        ])
                        ->withData([
                            'target' => $user->phoneNumber,
                            'message' => "Hai *".$user->name."*,\n\nPengajuan Anda dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah dicairkan.\n\nTerima kasih.
    \n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                        ])
                        ->post();



            //POST API KE JURNAL UMUM
            $token = Api::where('id', 1)->get()->pluck('token');
            $session = Api::where('id', 1)->get()->pluck('session');
            $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
                $data = array (
                  'detailJournalVoucher' =>
                  array (
                  0 =>
                  array (
                  'accountNo' => '12005',
                  'amount' => $nominal,
                  'amountType' => 'DEBIT',
                  'subsidiaryType' => 'EMPLOYEE',
                  'employeeNo' => $request->employeeNo,
                  'departmentName' => $request->departmentName,
                  'projectNo' => $request->projectNo,
                  ),
                  1 =>
                  array (
                  'accountNo' => $request->sumber,
                  'amount' => $nominal,
                  'amountType' => 'CREDIT',
                  'subsidiaryType' => 'EMPLOYEE',
                  'employeeNo' => $request->employeeNo,
                  'departmentName' => $request->departmentName,
                  'projectNo' => $request->projectNo,
                  ),
                  ),
                  'transDate' => $request->transDate,
                  'description' => $request->description,
            );

            $postData =  json_encode($data,JSON_UNESCAPED_SLASHES);
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

            DB::commit();
            return response()->json(['success' => 'Data Added successfully.']);
          } catch(\Exception $e) {
            DB::rollback();
            return response()->json(['success' => 'Error : '.$e->getMessage()]);
        } catch(\Throwable $e) {
            DB::rollback();
            return response()->json(['success' => 'Error : '.$e->getMessage()]);
        }
        

          //END POST API KE JURNAL UMUM

          // return response()->json(['success' => 'Data Added successfully.']);
      }

      public function storePushNotif(Request $request)
      {
          $rules = array(
              'id_user'    =>  'required',
              'id_pengajuan'    =>  'required',
              'id_pencairan'    =>  'required',
          );

          $error = Validator::make($request->all(), $rules);

          if($error->fails())
          {
              return response()->json(['errors' => $error->errors()->all()]);
          }

          $form_data = array(
              'id_pencairan'        =>  $request->id_pencairan,
              'id_pengajuan'        =>  $request->id_pengajuan,
              'id_user'        =>  $request->id_user,
              'jabatan'        =>  "Finance",
              'keterangan'        =>  "Melakukan permintaan pencairan dana berdasarkan termin sebesar ".$request->nominal."%",
              'created_by'        =>  Auth::user()->username,
              'updated_by'        =>  "-",
          );

          Notif_push::create($form_data);
          return response()->json(['success' => 'Data Added successfully.']);
      }

      public function storePushNotifOwner(Request $request)
      {
          $rules = array(
              'id_user'    =>  'required',
              'id_pengajuan'    =>  'required',
              'id_pencairan'    =>  'required',
          );

          $error = Validator::make($request->all(), $rules);

          if($error->fails())
          {
              return response()->json(['errors' => $error->errors()->all()]);
          }

          $form_data = array(
              'id_pencairan'        =>  $request->id_pencairan,
              'id_pengajuan'        =>  $request->id_pengajuan,
              'id_user'        =>  $request->id_user,
              'jabatan'        =>  "Owner",
              'keterangan'        =>  $request->keterangan,
              'created_by'        =>  Auth::user()->username,
              'updated_by'        =>  "-",
          );

          Notif_push::create($form_data);
          return response()->json(['success' => 'Data Added successfully.']);
      }

      public function getDetailPencairan(request $request) {
        $id = $request->id;
        $pencairan = DB::select( DB::raw("SELECT * FROM detail_pencairan WHERE id_pencairan='$id'"));
        $qry_metode = $pencairan['0']->metode;
        $qry_sumber = $pencairan['0']->sumber;
        $username = $pencairan['0']->created_by;
        $query_sumber = DB::table("listkasbank")->where("kode_kasbank",$qry_sumber)->pluck("nama_list");
        $query_metode = DB::table("kasbank")->where("kode_perkiraan",$qry_metode)->pluck("nama");
        $sumber = $query_sumber['0'];
        $metode = $query_metode['0'];
        $query = DB::table("pencairan")->where("id",$id)->pluck("id_pengajuan");
        $id_pengajuan = $query['0'];
        $query_proyek = DB::table("pengajuan")->where("id",$id_pengajuan)->pluck("id_project");
        $id_proyek  = $query_proyek['0'];
        $pengajuan = DB::select( DB::raw("SELECT * FROM pengajuan WHERE id='$id_pengajuan'"));
        $proyek = DB::select( DB::raw("SELECT * FROM master_project WHERE id='$id_proyek'"));
        $user = DB::select( DB::raw("SELECT name FROM users WHERE username='$username'"));
        $pengirim = $user['0']->name;
        return view('pencairan/detail_pencairan',['id'=>$id,'pencairan'=>$pencairan,'pengajuan'=>$pengajuan,'proyek'=>$proyek,'metode'=>$metode,'sumber'=>$sumber,'pengirim'=>$pengirim]);
      }

      public function project($id)
      {
        $states = DB::table("master_project")
        ->where("id",$id)
        ->pluck("nama","keterangan");
        return json_encode($states);
      }

      public function store(request $request){
        $id_user = Auth::user()->id;
        $nama = Auth::user()->name;
        $cek =  DB::table('pengajuan')
        ->max('id');

         $cek_hasil = $cek+1;

        $kitabs = new Pengajuan;
        $kitabs->id = $cek_hasil;
        $kitabs->no_pengajuan = $request->no_pengajuan;
        $kitabs->id_project = $request->id_project;
        $kitabs->id_user = $id_user;
        $kitabs->limit_sisa = $request->limit_sisa;
        $kitabs->nominal_pengajuan = $request->limit_sisa;
        $kitabs->status = 0;
        $kitabs->created_by = $nama;
        $kitabs->save();

        $hitung = count($request->id_kelompok);

        for ($i=0; $i < $hitung; $i++) {
          $tsd = new Listpengajuan;
          $tsd->id_pengajuan = $cek_hasil;
          $tsd->id_kelompok = $request->id_kelompok[$i];
          $tsd->id_daftar = $request->id_daftar[$i];
          $tsd->keterangan = $request->keterangan_pengajuan[$i];
          $tsd->created_by = $nama;
          $tsd->save();
        }
        return response()->json(['success' => 'Data Added successfully.']);

      }
      public function addtermin($id){
        $x = DB::table("pencairan")
        ->max('id');
        $d = $x+1;
        $tsd = new Pencairan;
        $tsd->id = $d;
        $tsd->id_pengajuan = $id;
        $tsd->save();

        return response()->json($d);
        // return response()->json(['success' => 'Data is successfully updated']);
      }

      public function updatedtermin(request $request){
        $hitung = count($request->nominal_termin);
        $nama= Auth::user()->name;
        // dd($request->nominal_termin);

        for ($i=0; $i < $hitung; $i++) {
          // print($i);
          $form_data = array(
              'nominal'     =>  $request->nominal_termin[$i],
              'date'        =>  $request->date[$i],
              'status'      =>  $request->status[$i],
              'created_by'  =>  $nama


          );
          Pencairan::whereId($request->id_termins[$i])->update($form_data);

        }

        return response()->json(['success' => 'Data is successfully updated']);

      }

      public function push(request $request){
        return response()->json(['success' => 'Data is successfully updated']);

      }

      public function delete($id){
          Pencairan::find($id)->delete();
          return response()->json(['success' => 'Data is successfully updated']);


      }




}
