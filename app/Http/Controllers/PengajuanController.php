<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use DataTables;
use App\Pengajuan;
use App\Pencairan;
use App\Tmp;
use App\Listpengajuan;
use Auth;

// use \Yajra\Datatables\Datatables;

class PengajuanController extends Controller
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
          $project = DB::table('master_project')
          ->get();
          $kelompok = DB::table('master_kelompok_kegiatan')
          ->get();
          $daftar = DB::table('master_daftar_rencana')
          ->get();

          if(request()->ajax())
          {

            $jabatan = Auth::user()->jabatan;
            $id_user = Auth::user()->id;

                if($jabatan == 'karyawan'){
                  $data = DB::table('pengajuan')
                  ->join('master_project','pengajuan.id_project','master_project.id')
                  ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
                  ->where('pengajuan.id_user', $id_user);
                  if(!empty($request->first) && !empty($request->last))
                              {
                                  $first = $request->first;
                                  $last = $request->last;
                                  $tahun = date("Y");
                                  $from = $tahun.'-'.$first.'-01';
                                  $to = $tahun.'-'.$last.'-30';

                                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                              }

                  $data = $data->orderBy('pengajuan.id', 'DESC');
                  $data = $data->get();
                  return datatables()->of($data)->addColumn('action', function ($data) {
                    if($data->status == 0 ){
                    $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                  }elseif ($data->status == 1) {
                    $button = '<button  class="view btn btn-primary btn-sm">PROSES</button>';
                  } elseif ($data->status == 2) {
                    $button = '<button   class="view btn btn-success btn-sm">APPROVED</button>';
                  } elseif ($data->status == 3) {
                    $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                  }
                  else {
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                  }
                    $button .= '&nbsp;&nbsp;';

                    return $button;

                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->rawColumns(['action','total'])
                  ->make(true);
                }elseif ($jabatan == 'Direktur Operasional') {
                  $data = DB::table('pengajuan')
                  ->join('master_project','pengajuan.id_project','master_project.id')
                  ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
                  ->where('pengajuan.status',0);
                  if(!empty($request->first) && !empty($request->last))
                              {
                                  $first = $request->first;
                                  $last = $request->last;
                                  $tahun = date("Y");
                                  $from = $tahun.'-'.$first.'-01';
                                  $to = $tahun.'-'.$last.'-30';

                                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                              }

                  $data = $data->orderBy('pengajuan.id', 'DESC');
                  $data = $data->get();
                  return datatables()->of($data)->addColumn('action', function ($data) {
                    $button = '<button name="edit" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                    return $button;

                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->rawColumns(['action','total'])
                  ->make(true);
                }elseif ($jabatan == 'Finance') {

                  $data = DB::table('pengajuan')
                  ->join('master_project','pengajuan.id_project','master_project.id')
                  ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
                  ->where('pengajuan.status',1);
                  if(!empty($request->first) && !empty($request->last))
                              {
                                  $first = $request->first;
                                  $last = $request->last;
                                  $tahun = date("Y");
                                  $from = $tahun.'-'.$first.'-01';
                                  $to = $tahun.'-'.$last.'-30';

                                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                              }

                  $data = $data->orderBy('pengajuan.id', 'DESC');
                  $data = $data->get();
                  return datatables()->of($data)->addColumn('action', function ($data) {
                    if($data->status == 0 ){
                    $button = '<button  class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                  }elseif ($data->status == 1) {
                    $button = '<button  name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses view btn btn-primary btn-sm">PROSES</button>';
                  } elseif ($data->status == 2) {
                    $button = '<button   class="prosesowner view btn btn-success btn-sm">APPROVED Finance</button>';
                  } elseif ($data->status == 3) {
                    $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                  }
                  else {
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                  }
                    $button .= '&nbsp;&nbsp;';

                    return $button;

                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->rawColumns(['action','total'])
                  ->make(true);

                  // code...
                }elseif ($jabatan == 'Owner') {
                  $data = DB::table('pengajuan')
                  ->join('master_project','pengajuan.id_project','master_project.id')
                  ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
                  ->where('pengajuan.status',2);
                  if(!empty($request->first) && !empty($request->last))
                              {
                                  $first = $request->first;
                                  $last = $request->last;
                                  $tahun = date("Y");
                                  $from = $tahun.'-'.$first.'-01';
                                  $to = $tahun.'-'.$last.'-30';

                                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                              }

                  $data = $data->orderBy('pengajuan.id', 'DESC');
                  $data = $data->get();
                  return datatables()->of($data)->addColumn('action', function ($data) {
                    if($data->status == 0 ){
                    $button = '<button  class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                  }elseif ($data->status == 1) {
                    $button = '<button  class="proses view btn btn-primary btn-sm">PROSES</button>';
                  } elseif ($data->status == 2) {
                    $button = '<button  name="prosesowner" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="prosesowner view btn btn-success btn-sm">APPROVED Finance</button>';
                  } elseif ($data->status == 3) {
                    $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                  }
                  else {
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                  }
                    $button .= '&nbsp;&nbsp;';

                    return $button;

                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->rawColumns(['action','total'])
                  ->make(true);
                }
                elseif ($jabatan == 'superadmin') {

                  $data = DB::table('pengajuan')
                  ->join('master_project','pengajuan.id_project','master_project.id')
                  ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan');
                  if(!empty($request->first) && !empty($request->last))
                              {
                                  $first = $request->first;
                                  $last = $request->last;
                                  $tahun = date("Y");
                                  $from = $tahun.'-'.$first.'-01';
                                  $to = $tahun.'-'.$last.'-30';

                                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                              }

                  $data = $data->orderBy('pengajuan.id', 'DESC');
                  $data = $data->get();
                  return datatables()->of($data)
                  ->addColumn('action', function ($data) {
                    if($data->status == 0 ){
                    $button = '<button name="edit" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                  }elseif ($data->status == 1) {
                    $button = '<button  name="proses" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="proses view btn btn-primary btn-sm">PROSES</button>';
                  } elseif ($data->status == 2) {
                    $button = '<button  name="prosesowner" id="'.$data->id.'" data-toggle="modal" data-target=".bd-example-modal-xl" class="prosesowner view btn btn-success btn-sm">APPROVED Finance</button>';
                  } elseif ($data->status == 3) {
                    $button = '<button  class=" view btn btn-success btn-sm">APPROVED Owner</button>';
                  }
                  else {
                    $button = '<button  class="view btn btn-danger btn-sm">TOLAK</button>';
                  }
                    $button .= '&nbsp;&nbsp;';

                    return $button;

                  })
                  ->addColumn('total', function ($data) {
                    $button ='';
                      $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                    return $button;
                  })
                  ->rawColumns(['action','total'])
                  ->make(true);
                }

            }

          return view('pengajuan.index',['project' => $project, 'kelompok' => $kelompok, 'daftar' => $daftar ]);
      }

      public function proyek(request $request){
        $project = DB::table('master_project')
       ->where('no_project', 'like', $request->q.'%')
        ->get();
        return response()->json($project);
      }

      public function totalpengajuan(Request $request){
        $data = DB::table('pengajuan')
        ->join('master_project','pengajuan.id_project','master_project.id')
        ->select( DB::raw('SUM(pengajuan.nominal_pengajuan) as total'))
        ->where('pengajuan.status',3);
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

          $array = array();
          foreach ($data as $key) {
            $array[] = number_format( $key->total,0, ',' , '.');

          }

              return json_encode($array);

      }

      public function project($id)
      {
        $states = DB::table("master_project")
        ->where("id",$id)
        ->get();
        $total = DB::table("pengajuan")
        ->where("id_project",$id)
        ->count();

        return response()->json(['pro' => $states, 'max' => $total]);

      }
      public function searchkelompok($id){
        $states = DB::table("master_kelompok_kegiatan")
        ->join('budgetproject','master_kelompok_kegiatan.id_kelompok','budgetproject.id_kelompok')
        ->where("budgetproject.id_proyek",$id)
        ->pluck('master_kelompok_kegiatan.nama','master_kelompok_kegiatan.id_kelompok');
        return json_encode($states);
      }

      public function searchrencana($id, $project){
        $states = DB::table("master_daftar_rencana")
        ->join('budgetproject','master_daftar_rencana.id_daftar','budgetproject.id_daftar')
        ->where("budgetproject.id_kelompok",$id)
        ->where("budgetproject.id_proyek",$project)
        ->pluck('master_daftar_rencana.nama','master_daftar_rencana.id_daftar');
        return json_encode($states);
      }

      public function edit($id)
      {
          if(request()->ajax())
          {
              $data = DB::table("pengajuan")
                ->join('master_project','pengajuan.id_project','master_project.id')
                ->join('users','pengajuan.id_user','users.id')
                ->select('pengajuan.*','master_project.no_project','master_project.nama as nama_project','master_project.keterangan as keterangan_project','users.name as name_user','users.nik','users.jabatan')
                ->where("pengajuan.id",$id)
                ->get();
              return response()->json(['data' => $data]);
          }
      }

      public function detail($id){
        $data = DB::table("list_pengajuan")
        ->join('master_kelompok_kegiatan','list_pengajuan.id_kelompok','master_kelompok_kegiatan.id_kelompok')
        ->join('master_daftar_rencana','list_pengajuan.id_daftar','master_daftar_rencana.id_daftar')
        ->select('list_pengajuan.id','list_pengajuan.limit','list_pengajuan.keterangan','list_pengajuan.nominal_pengajuan','master_kelompok_kegiatan.nama as nama_kelompok','master_daftar_rencana.nama as nama_daftar')
        ->where("list_pengajuan.id_pengajuan",$id)
        ->orderBy('list_pengajuan.id')
        ->get();
        $cou = DB::table("list_pengajuan")
        ->join('master_kelompok_kegiatan','list_pengajuan.id_kelompok','master_kelompok_kegiatan.id_kelompok')
        ->join('master_daftar_rencana','list_pengajuan.id_daftar','master_daftar_rencana.id_daftar')
        ->select('list_pengajuan.limit','list_pengajuan.keterangan','list_pengajuan.nominal_pengajuan','master_kelompok_kegiatan.nama as nama_kelompok','master_daftar_rencana.nama as nama_daftar')
        ->where("list_pengajuan.id_pengajuan",$id)
        ->count();
        return response()->json(['hasil' => $data, 'cek' => $cou]);
      }

      public function searchbudget($project, $kelompok, $daftar, $kd){
        $lu = DB::table("tmp_pengajuan")
        ->where("kd_list",$kd)
        ->where("id_proyek",$project)
        ->where("id_kelompok",$kelompok)
        ->where("id_daftar",$daftar)
        ->count();

        if($lu != 0){
          $states = DB::table("tmp_pengajuan")
          ->where("kd_list",$kd)
          ->where("id_proyek",$project)
          ->where("id_kelompok",$kelompok)
          ->where("id_daftar",$daftar)
          ->orderBy("id","DESC")
          ->pluck('limit','id');
        }else {
          $states = DB::table("budgetproject")
          ->where("id_proyek",$project)
          ->where("id_kelompok",$kelompok)
          ->where("id_daftar",$daftar)
          ->pluck('limit','id');
        }

        return json_encode($states);
      }

      public function tmp($id_proyek, $id_kelompok, $id_daftar, $list, $kd, $ks, $unik){
        $kl  = $ks - $kd;
        $tsd = new Tmp;
        $tsd->id_proyek = $id_proyek;
        $tsd->id_kelompok = $id_kelompok;
        $tsd->id_daftar = $id_daftar;
        $tsd->kd_list = $list;
        $tsd->limit = $kl;
        $tsd->nominal_awal = $ks;
        $tsd->kd_unik = $unik;
        $tsd->save();

        return response()->json(['success' => 'Data is successfully updated']);
      }

      public function deletetmp($id_proyek, $id_kelompok, $id_daftar, $list, $unik){
//// cek limit dengan id yg sama
        $c = DB::table("tmp_pengajuan")
        ->where("id_proyek",$id_proyek)
        ->where("id_kelompok",$id_kelompok)
        ->where("id_daftar",$id_daftar)
        ->where("kd_list",$list)
        ->orderBy("id","DESC")
        ->pluck('limit');
// cek limit terakhir berdasarkan ID
        $x = DB::table("tmp_pengajuan")
        ->where("kd_unik",$unik)
        ->orderBy("id","DESC")
        ->pluck('id');

        $f = DB::table("tmp_pengajuan")
        ->where("kd_unik",$unik)
        ->orderBy("id","DESC")
        ->pluck('limit');

        $d = count($c);
        if($x[0] != null){
        if($d > 1){
          // $fy = $c[1]+$f[0];
          // $form_data = array(
          //     'nominal_awal'        =>  $fy,
          // );
          // Tmp::whereId($x)->update($form_data);
          $x = DB::table("tmp_pengajuan")
          ->where("kd_unik",$unik)
          ->orderBy("id","DESC")
          ->pluck('id');
          Tmp::find($x[0])->delete();


        }else {
          $x = DB::table("tmp_pengajuan")
          ->where("kd_unik",$unik)
          ->orderBy("id","DESC")
          ->pluck('id');

          Tmp::find($x[0])->delete();

        }
      }

      return response()->json(['success' => 'Data is successfully updated']);

      }

      public function update(request $request){
        $nama= Auth::user()->name;
        $nominal_pengajuan = preg_replace('/\D/','',$request->total_pengajuan);

        $data = Pengajuan::where('id',$request->id_pengajuan)->first();
        
        if($data->status == 0) {

          $form_data = array(
              'nominal_pengajuan'    =>  $nominal_pengajuan,
              'status'               =>  1,
              'menyetujui_op'        =>  $nama
  
  
          );
          Pengajuan::whereId($request->id_pengajuan)->update($form_data);
        }
        $date = date('Y-m-d H:i:s');
        $id_user= Auth::user()->name;
        DB::insert('insert into notif (data, created_at, created_by) values (?, ?, ?)', ['Nominal Pengajuan Telah Di Perbaharui dan Disetujui Head Department Senilai '.$nominal_pengajuan, $date, $id_user]);
       
        $pengajuan = $request->id_list;
        for($i = 0; $i < count($pengajuan); $i++)
          {
            $d = $request->nominal_pengajuan[$i];
            $nom = preg_replace('/\D/','',$d);


            $form_date = array(
                'nominal_pengajuan'        =>  $nom
            );
            Listpengajuan::whereId($request->id_list[$i])->update($form_date);

          }

                
        $dt = Pengajuan::find($request->id_pengajuan);
        $user = \App\User::where('id',$dt->id_user)->first();
        $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders([
                        'Authorization: G-BJE9txd#aXDewvme7u'
                    ])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' => "Hai *".$user->name."*,\n\nPengajuan Anda dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                    ])
                    ->post();

        $dirops = \App\User::where('jabatan','Finance')->get();

        foreach ($dirops as $value) {
          
          $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders([
                      'Authorization: G-BJE9txd#aXDewvme7u'
                  ])
                  ->withData([
                      'target' => $value->phoneNumber,
                      'message' => "Hai *".$value->name."*,\n\nPengajuan dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                  ])
                  ->post();

        }
        

        return response()->json(['success' => 'Data is successfully updated']);

      }

      public function approvefinace(request $request){
        $nama= Auth::user()->name;

          $form_data = array(
              'status'        =>  2,
              'menyetujui'        =>  $nama
  
  
          );
          Pengajuan::whereId($request->id_pengajuan)->update($form_data);

        $c = DB::table("pengajuan")
            ->where('id',$request->id_pengajuan)
            ->pluck('no_pengajuan');
        $date = date('Y-m-d H:i:s');
        $id_user= Auth::user()->name;
        DB::insert('insert into notif (data, created_at, created_by) values (?, ?, ?)', ['Pengajuan Telah Disetujui Oleh Finance dengan ID '.$c[0], $date, $id_user]);

        
        $dt = Pengajuan::find($request->id_pengajuan);
        $user = \App\User::where('id',$dt->id_user)->first();
        $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders([
                        'Authorization: G-BJE9txd#aXDewvme7u'
                    ])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' => "Hai *".$user->name."*,\n\nPengajuan Anda dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima oleh HR GA.\n\nSaat ini sedang menunggu Proses Verifikasi Direktur Utama.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                    ])
                    ->post();

        $dirops = \App\User::where('jabatan','Owner')->get();

        foreach ($dirops as $value) {
          
          $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders([
                      'Authorization: G-BJE9txd#aXDewvme7u'
                  ])
                  ->withData([
                      'target' => $value->phoneNumber,
                      'message' => "Hai *".$value->name."*,\n\nPengajuan dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                  ])
                  ->post();

        }
        return response()->json(['success' => 'Data is successfully updated']);

      }

      public function approveowner(request $request){
        $nama= Auth::user()->name;

        $form_data = array(
            'status'        =>  3,
            'mengetahui'        =>  $nama


        );
        Pengajuan::whereId($request->id_pengajuan)->update($form_data);
        $date = date('Y-m-d H:i:s');
        $c = DB::table("pengajuan")
            ->where('id',$request->id_pengajuan)
            ->pluck('no_pengajuan');
            $id_user= Auth::user()->name;

        DB::insert('insert into notif (data, created_at, created_by) values (?, ?, ?)', ['Pengajuan Telah Disetujui Oleh Owner dengan ID '.$c[0], $date, $id_user]);

        $hitung = count($request->nominal_termin);

        for ($i=0; $i < $hitung; $i++) {
          if ($request->nominal_termin[$i] == '') {
            $nominal = 100;
          }else {
            $nominal = $request->nominal_termin[$i];
          }
          $tsd = new Pencairan;
          $tsd->id_pengajuan = $request->id_pengajuan;
          $tsd->nominal = $nominal;
          $tsd->date = $request->date[$i];
          $tsd->status = 0;
          $tsd->created_by = $nama;
          $tsd->save();
        }
 
        $dt = Pengajuan::find($request->id_pengajuan);
        $user = \App\User::where('id',$dt->id_user)->first();
        $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders([
                        'Authorization: G-BJE9txd#aXDewvme7u'
                    ])
                    ->withData([
                        'target' => $user->phoneNumber,
                        'message' => "Hai *".$user->name."*,\n\nPengajuan Anda dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Pencairan Finance.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                    ])
                    ->post();

        $dirops = \App\User::where('jabatan','Finance')->get();

        foreach ($dirops as $value) {
          
          $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders([
                      'Authorization: G-BJE9txd#aXDewvme7u'
                  ])
                  ->withData([
                      'target' => $value->phoneNumber,
                      'message' => "Hai *".$value->name."*,\n\nPengajuan dengan *".$dt->no_pengajuan."* sebesar *Rp ".number_format($dt->nominal_pengajuan,0,',','.')."* telah diterima Direktur Utama.\n\nSilahkan lanjutkan proses pencairan.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                  ])
                  ->post();

        }
        return response()->json(['success' => 'Data is successfully updated']);

      }


      public function store(request $request){
        $id_user = Auth::user()->id;
        $nama = Auth::user()->name;
        $cek =  DB::table('pengajuan')
        ->max('id');

        $nominal_pengajuan = preg_replace('/\D/','',$request->total_pengajuan);


        $cek_hasil = $cek+1;

        $kitabs = new Pengajuan;
        $kitabs->id = $cek_hasil;
        $kitabs->no_pengajuan = $request->no_pengajuan;
        $kitabs->id_project = $request->id_project;
        $kitabs->id_user = $id_user;
        $kitabs->nominal_pengajuan = $nominal_pengajuan;
        $kitabs->status = 0;
        $kitabs->created_by = $nama;
        $kitabs->save();

        $date = date('Y-m-d H:i:s');
        $id_user= Auth::user()->name;
        DB::insert('insert into notif (data, created_at, created_by) values (?, ?, ?)', ['Telah Melakukan Pengajuan Dana Sebesar '.$nominal_pengajuan.' Dengan Nomor Pengajuan'.$request->no_pengajuan, $date, $id_user]);

        $hitung = count($request->id_kelompok);

        for ($i=0; $i < $hitung; $i++) {
          $b = $request->id_budget[$i];
          $c = $request->nominal_pengajuan[$i];
          $limit = preg_replace('/\D/','',$b);
          $nominal = preg_replace('/\D/','',$c);

          $tsd = new Listpengajuan;
          $tsd->id_pengajuan = $cek_hasil;
          $tsd->id_kelompok = $request->id_kelompok[$i];
          $tsd->id_daftar = $request->id_daftar[$i];
          $tsd->limit = $limit;
          $tsd->keterangan = $request->keterangan_pengajuan[$i];
          $tsd->nominal_pengajuan = $nominal;
          $tsd->created_by = $nama;
          $tsd->save();
        }

        $pengajuan = Pengajuan::find($cek_hasil);

        $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders([
                        'Authorization: G-BJE9txd#aXDewvme7u'
                    ])
                    ->withData([
                        'target' => auth()->user()->phoneNumber,
                        'message' => "Hai *".auth()->user()->name."*,\n\nPengajuan Anda dengan *".$pengajuan->no_pengajuan."* sebesar *Rp ".number_format($pengajuan->nominal_pengajuan,0,',','.')."* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi Head Department.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                        // 'message' => "Pengajuan ".$pengajuan->no_pengajuan." telah diterima dan sedang proses verifikasi Direktur Operasional"
                    ])
                    ->post();
        
        $dirops = \App\User::where('jabatan','Direktur Operasional')->get();

        foreach ($dirops as $value) {
          
          $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders([
                      'Authorization: G-BJE9txd#aXDewvme7u'
                  ])
                  ->withData([
                      'target' => $value->phoneNumber,
                      'message' => "Hai *".$value->name."*,\n\nPengajuan dengan *".$pengajuan->no_pengajuan."* sebesar *Rp ".number_format($pengajuan->nominal_pengajuan,0,',','.')."* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
\n\nKlik untuk melihat detail pengajuan : ".url('/pengajuan')
                  ])
                  ->post();

        }
        
        return response()->json(['success' => 'Data Added successfully.']);

      }



}
