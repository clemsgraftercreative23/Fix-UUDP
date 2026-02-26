<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use DataTables;
use App\Pengajuan;
use App\Pertanggungjawaban;
use App\Detail_pertanggungjawaban;
use App\Finish_pertanggungjawaban;
use App\Listpengajuan;
use App\User;
use App\Master_project;
use App\Api;
use Auth;
use Validator;
// use \Yajra\Datatables\Datatables;

class PertanggungjawabanuudpController extends Controller
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
        $data = DB::table('pengajuan')
        ->join('master_project','pengajuan.id_project','master_project.id')
        ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
        ->where('pengajuan.sisa_pengajuan', 0);
        $data = $data->get();
        return datatables()->of($data)
        ->addColumn('action', function ($data) {
        if($data->sisa_pelaporan != 0){
           $button = ' <button type="button" name="report" data-toggle="modal" data-target="#formModal" id="'.$data->id.'" class="report btn btn-success btn-sm">LAPORKAN</button>';
        }else {
            $button = '<button type="button" name="report" data-toggle="modal" data-target="#formModal" id="'.$data->id.'" class="report btn btn-success btn-sm">LAPORKAN</button>';
        }
        $button .= '&nbsp;&nbsp;';
        return $button;

        })->addColumn('pertanggungjawaban', function ($data) {
        $button = '<label style="font-size:15px;"> Rp'.$data->nominal_pengajuan.'</label><br><span style="font-size:10px;"> Selisih: Rp'.$data->sisa_pelaporan.'</span>';
        $button .= '&nbsp;&nbsp;';
        return $button;
        })->rawColumns(['action','pertanggungjawaban'])->make(true);
        }
        return view('pertanggungjawaban.index',['project' => $project, 'kelompok' => $kelompok, 'daftar' => $daftar ]);
    }

    public function insertPertanggungjawaban($id) {
        $pengajuan = DB::select( DB::raw("SELECT * FROM pengajuan WHERE no_pengajuan='$id'"));
        $id_pengajuan = $pengajuan['0']->id;
        $id_user = $pengajuan['0']->id_user;
        $id_project = $pengajuan['0']->id_project;
        $user = User::findOrFail($id_user);
        $project = Master_project::findOrFail($id_project);
        $username = $user->username;
        $no_project = $project->no_project;
        $list_pengajuan = DB::select( DB::raw("SELECT *,list_pengajuan.id AS id_main, master_kelompok_kegiatan.nama AS nama_kelompok, master_daftar_rencana.nama AS nama_daftar FROM list_pengajuan LEFT JOIN master_kelompok_kegiatan ON list_pengajuan.id_kelompok=master_kelompok_kegiatan.id_kelompok LEFT JOIN master_daftar_rencana ON list_pengajuan.id_daftar=master_daftar_rencana.id_daftar WHERE id_pengajuan='$id_pengajuan' ORDER BY list_pengajuan.id ASC"));
        $kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
        $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY id DESC"));
        $count_status = DB::table('list_pengajuan')->where('id_pengajuan',$id_pengajuan)->where('status_pertanggungjawaban','!=','1')->count();
        $dana_dilaporkan = DB::table('detail_pertanggungjawaban')->where('id_pengajuan',$id_pengajuan)->where('status_tambahan',0)->selectRaw('sum(nominal_realisasi) as dana')->get();
        $cek_view = DB::table('finish_pertanggungjawaban')->where('id_pengajuan',$id_pengajuan)->count();
        if ($cek_view==0) {
            return view('pertanggungjawaban.addPertanggungjawaban',['pengajuan'=>$pengajuan,'list_pengajuan'=>$list_pengajuan,'departemen'=>$departemen,'kasbank'=>$kasbank,'count_status'=>$count_status,'dana_dilaporkan'=>$dana_dilaporkan,'id_pengajuan'=>$id_pengajuan,'username'=>$username,'no_project'=>$no_project]);
        } else {
            $pj = DB::select( DB::raw("SELECT *,nominal_realisasi,images,nama_departemen,deskripsi FROM list_pengajuan LEFT JOIN detail_pertanggungjawaban ON list_pengajuan.id = detail_pertanggungjawaban.id_listpengajuan LEFT JOIN pertanggungjawaban ON list_pengajuan.id=pertanggungjawaban.id_listpengajuan  LEFT JOIN departemen ON detail_pertanggungjawaban.id_departemen=departemen.id_dep WHERE detail_pertanggungjawaban.id_pengajuan='$id_pengajuan' ORDER BY list_pengajuan.id ASC"));
            $finish = DB::select( DB::raw("SELECT * FROM finish_pertanggungjawaban LEFT JOIN kasbank ON finish_pertanggungjawaban.metode=kasbank.kode_perkiraan LEFT JOIN listkasbank ON finish_pertanggungjawaban.sumber=listkasbank.kode_kasbank WHERE id_pengajuan='$id_pengajuan'"));
            return view('pertanggungjawaban.finishPertanggungjawaban',['pengajuan'=>$pengajuan,'list_pengajuan'=>$list_pengajuan,'departemen'=>$departemen,'kasbank'=>$kasbank,'count_status'=>$count_status,'dana_dilaporkan'=>$dana_dilaporkan,'id_pengajuan'=>$id_pengajuan,'pj'=>$pj,'finish'=>$finish]);
        }

    }

    public function getPertanggungjawabanUudp(Request $request){
        $id_listpengajuan = $request->id;
        $pengajuan = DB::select( DB::raw("SELECT * FROM list_pengajuan WHERE id='$id_listpengajuan'"));
        $id_subinduk = $pengajuan['0']->id_daftar;
        $daftar_rencana = DB::select( DB::raw("SELECT * FROM master_daftar_rencana WHERE id_daftar='$id_subinduk'"));
        $noWithIndent = $daftar_rencana['0']->noWithIndent;
        $pengajuan = DB::select( DB::raw("SELECT * FROM list_pengajuan WHERE id='$id_listpengajuan'"));
        $id_pengajuan = $pengajuan['0']->id_pengajuan;
        $kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
        $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY id DESC"));
        $a = 'aaaa';
        return view('pertanggungjawaban/detailPertanggungjawaban',['departemen'=>$departemen,'id_listpengajuan'=>$id_listpengajuan,'id_pengajuan'=>$id_pengajuan,'noWithIndent'=>$noWithIndent]);
    }

    public function changePertanggungjawaban(Request $request){
        $id_listpengajuan = $request->id;
        $pengajuan = DB::select( DB::raw("SELECT * FROM list_pengajuan WHERE id='$id_listpengajuan'"));
        $id_pengajuan = $pengajuan['0']->id_pengajuan;
        $id_subinduk = $pengajuan['0']->id_daftar;
        $daftar_rencana = DB::select( DB::raw("SELECT * FROM master_daftar_rencana WHERE id_daftar='$id_subinduk'"));
        $noWithIndent = $daftar_rencana['0']->noWithIndent;
        $cek_pertanggungjawaban = DB::select( DB::raw("SELECT * FROM pertanggungjawaban WHERE id_listpengajuan='$id_listpengajuan'"));
        $id_pertanggungjawaban = $cek_pertanggungjawaban['0']->id;
        $detail_pertanggungjawaban = DB::select( DB::raw("SELECT * FROM detail_pertanggungjawaban WHERE id_pertanggungjawaban='$id_pertanggungjawaban'"));
        $pertanggungjawaban = DB::select( DB::raw("SELECT * FROM pertanggungjawaban WHERE id='$id_pertanggungjawaban'"));
        $kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
        $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY id DESC"));
        return view('pertanggungjawaban/changePertanggungjawaban',['departemen'=>$departemen,'id_listpengajuan'=>$id_listpengajuan,'id_pengajuan'=>$id_pengajuan,'pertanggungjawaban'=>$pertanggungjawaban,'detail_pertanggungjawaban'=>$detail_pertanggungjawaban,'noWithIndent'=>$noWithIndent]);
    }

    public function fetchData(Request $request,$id) {

        if($request->ajax())
        {
            $data = DB::table('detail_pertanggungjawaban')->select('detail_pertanggungjawaban.*','nama_departemen')->join('departemen','detail_pertanggungjawaban.id_departemen','departemen.id_dep')->where('id_pertanggungjawaban',$id)->orderBy('detail_pertanggungjawaban.id','asc')->get();
            echo json_encode($data);
        }
    }


    // public function addPertanggungjawaban(Request $request){
    //       $id = $request->id;
    //       $pengajuan = DB::select( DB::raw("SELECT *FROM pengajuan WHERE id='$id'"));
    //       $id_user = $pengajuan['0']->id_user;
    //       $user = User::findOrFail($id_user);
    //       $id_project = $pengajuan['0']->id_project;
    //       $project = Master_project::findOrFail($id_project);
    //       $list_pengajuan = DB::select( DB::raw("SELECT *, master_kelompok_kegiatan.nama AS nama_kelompok, master_daftar_rencana.nama AS nama_daftar FROM list_pengajuan LEFT JOIN master_kelompok_kegiatan ON list_pengajuan.id_kelompok=master_kelompok_kegiatan.id_kelompok LEFT JOIN master_daftar_rencana ON list_pengajuan.id_daftar=master_daftar_rencana.id_daftar WHERE id_pengajuan='$id'"));
    //       $kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
    //       $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY id DESC"));
    //       return view('pertanggungjawaban/addPertanggungjawabancopy',['id'=>$id,'list_pengajuan'=>$list_pengajuan,'kasbank'=>$kasbank,'pengajuan'=>$pengajuan,'departemen'=>$departemen,'user'=>$user,'project'=>$project]);
    // }

    public function project($id)
    {
      $states = DB::table("master_project")
      ->where("id",$id)
      ->pluck("nama","keterangan");
      return json_encode($states);
    }


    public function store(request $request){

        $rules = array(
            'id_pengajuan'    =>  'required',
            'id_listpengajuan'    =>  'required',
            'deskripsi'    =>  'required',
        );
        $error = Validator::make($request->all(), $rules);

        if($error->fails())
        {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $form_data = array(
            'id_pengajuan' => $request->id_pengajuan,
            'id_listpengajuan' => $request->id_listpengajuan,
            'deskripsi'        =>  $request->deskripsi,
            'created_by' => Auth::user()->username
        );

        Pertanggungjawaban::create($form_data);

        $this->validate($request, [
                'images' => 'required',
                'images.*' => 'mimes:jpg,jpeg,png,pdf,mp4'
        ]);

        $id_max = Pertanggungjawaban::max('id');
        $user = Auth::user()->username;
        $images=array();
        $desc=array();
        $nominal_realisasi=str_replace(".", "", $request->nominal_realisasi);
        $id_departemen = $request->id_departemen;
        $id_listpeng = $request->id_listpeng;
        $id_peng = $request->id_peng;
        $noWithIndent = $request->noWithIndent;
        $input=$request->all();
        if($files=$request->file('images')){
            foreach( $files as $index => $file ) {
                $name=rand() . '.' . $file->getClientOriginalExtension();
                $file->move('images/pertanggungjawaban',$name);
                $images[]=$name;
                DB::table('detail_pertanggungjawaban')->insert([
                'id_pertanggungjawaban' => $id_max,
                'nominal_realisasi' =>$nominal_realisasi[$index],
                'id_departemen' =>$id_departemen[$index],
                'id_listpengajuan' =>$id_listpeng[$index],
                'id_pengajuan' =>$id_peng[$index],
                'noWithIndent' =>$noWithIndent[$index],
                'images' => $name,
                'amountType'        =>  "DEBIT",
                'status_tambahan'        =>  0,
                'subsidiaryType' => 'EMPLOYEE',
                'created_by'=>$user,
                'updated_by'=>'-'
                ]);
            }
        }

        $form_status = array(
            'status_pertanggungjawaban'        =>  1
        );
        Listpengajuan::whereId($request->id_listpengajuan)->update($form_status);

        return response()->json(['success' => 'Data Added successfully.']);
    }

    public function change(request $request){

        $rules = array(
            'deskripsi'    =>  'required',
        );
        $error = Validator::make($request->all(), $rules);

        if($error->fails())
        {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $form_data = array(
            'deskripsi'        =>  $request->deskripsi,
            'updated_by' => Auth::user()->username
        );

        Pertanggungjawaban::whereId($request->id_pertanggungjawaban)->update($form_data);

        // $this->validate($request, [
        //         'images' => 'required',
        //         'images.*' => 'mimes:jpg,jpeg,png,pdf,mp4'
        // ]);

        $id_max = Pertanggungjawaban::max('id');
        $user = Auth::user()->username;
        $images=array();
        $desc=array();
        $nominal_realisasi=str_replace(".", "", $request->nominal_realisasi);
        $id_departemen = $request->id_departemen;
        $id_listpeng = $request->id_listpeng;
        $id_peng = $request->id_peng;
        $noWithIndent = $request->noWithIndent;
        $input=$request->all();
        if($files=$request->file('images')){
            foreach( $files as $index => $file ) {
                $name=rand() . '.' . $file->getClientOriginalExtension();
                $file->move('images/pertanggungjawaban',$name);
                $images[]=$name;
                DB::table('detail_pertanggungjawaban')->insert([
                'id_pertanggungjawaban' => $id_max,
                'nominal_realisasi' =>$nominal_realisasi[$index],
                'id_departemen' =>$id_departemen[$index],
                'id_listpengajuan' =>$id_listpeng[$index],
                'id_pengajuan' =>$id_peng[$index],
                'noWithIndent' =>$noWithIndent[$index],
                'images' => $name,
                'amountType'        =>  "DEBIT",
                'status_tambahan'        =>  0,
                'subsidiaryType' => 'EMPLOYEE',
                'created_by'=>$user,
                'updated_by'=>$user,
                ]);
            }
        }

        $form_status = array(
            'status_pertanggungjawaban'        =>  1,
            'updated_by' => $user
        );
        Listpengajuan::whereId($request->id_listpengajuan)->update($form_status);

        return response()->json(['success' => 'Data Added successfully.']);
    }

    public function finish(Request $request)
    {
         $nominal_sisa = str_replace(".", "", $request->nominal_sisa);
         $data = [
            [
              'id_pertanggungjawaban' => 0,
              'images' => "no images",
              'nominal_realisasi'        =>  $request->nominal_total,
              'id_departemen'        =>  103,
              'id_listpengajuan'        =>  0,
              'id_pengajuan'        =>  $request->id_pengajuan,
              'status_tambahan'        =>  1,
              'amountType'        =>  "CREDIT",
              'subsidiaryType' => 'EMPLOYEE',
              'noWithIndent' => 12005,
              'created_by' => AUth::user()->username
            ],
            [
              'id_pertanggungjawaban' => 0,
              'images' => "no images",
              'nominal_realisasi'        =>  $nominal_sisa,
              'id_departemen'        =>  103,
              'id_listpengajuan'        =>  0,
              'id_pengajuan'        =>  $request->id_pengajuan,
              'status_tambahan'        =>  1,
              'amountType'        =>  "DEBIT",
              'subsidiaryType' => 'EMPLOYEE',
              'noWithIndent' => $request->sumber,
              'created_by' => AUth::user()->username
            ],
            
        ];

        Detail_pertanggungjawaban::insert($data);

        $rules = array(
            'id_pengajuan'    =>  'required',
            'jenis_pengembalian'    =>  'required',
            'metode'    =>  'required',
            'sumber'    =>  'required',
            'nominal_sisa'    =>  'required',
            'tanggal'    =>  'required',
            'judul'    =>  'required',
        );
        $error = Validator::make($request->all(), $rules);

        $form_data = array(
            'id_pengajuan' => $request->id_pengajuan,
            'jenis_pengembalian'        =>  $request->jenis_pengembalian,
            'metode'        =>  $request->metode,
            'sumber'        =>  $request->sumber,
            'created_by' => AUth::user()->username,
            'nominal_sisa'        =>  $nominal_sisa,
            'tanggal'        =>  date('Y-m-d', strtotime($request->tanggal_pertanggungjawaban)),
            'judul'        =>  $request->judul
        );

        Finish_pertanggungjawaban::create($form_data);

        // return response()->json(['success' => 'Data is successfully updated']);


        //POST API KE JURNAL UMUM
        $token = Api::where('id', 1)->get()->pluck('token');
        $session = Api::where('id', 1)->get()->pluck('session');
        $url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
        $api = DB::select( DB::raw("SELECT nominal_realisasi as amount,amountType,subsidiaryType,detail_pertanggungjawaban.created_by AS employeeNo,nama_departemen AS departmentName,no_project AS projectNo,noWithIndent AS accountNo from detail_pertanggungjawaban LEFT JOIN departemen ON detail_pertanggungjawaban.id_departemen = departemen.id_dep LEFT JOIN pengajuan ON detail_pertanggungjawaban.id_pengajuan = pengajuan.id LEFT JOIN master_project ON pengajuan.id_project = master_project.id WHERE id_pengajuan='$request->id_pengajuan'"));
        $tglpost = date("d/m/Y", strtotime($request->tanggal_pertanggungjawaban));
        $dataapi =  [
                      'detailJournalVoucher' => $api, 
                      'transDate' => $tglpost,
                      'description' => $request->judul
                    ];

        // $items = json_decode(json_encode($dataapi), true);

        // print_r($dataapi);

        // $postData =  json_encode($dataapi,JSON_UNESCAPED_SLASHES); 
        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // curl_setopt($ch, CURLOPT_ENCODING , 1);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //   "Content-Type: application/json",
        //   'header' => "Authorization: Bearer ".$token['0'],
        //   'X-Session-ID: '.$session['0']));
        // $result = curl_exec($ch);

        // curl_close($ch);
        //END POST API KE JURNAL UMUM
        return response()->json(['success' => 'Data Added successfully.']);

    }


    public function deleteData(Request $request) {
        if($request->ajax())
        {
            DB::table('detail_pertanggungjawaban')
                ->where('id', $request->id)
                ->delete();
            echo '<div class="alert alert-success">Data Dihapus!</div>';
        }


    }


}
