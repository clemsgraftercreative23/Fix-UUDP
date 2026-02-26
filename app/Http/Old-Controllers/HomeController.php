<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
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
         if(!empty($request->firsts))
                      {
                          $first = $request->firsts;
                          $last = $request->firsts;
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
         ->where('pengajuan.status','0');
         if(!empty($request->firsts))
                      {
                          $first = $request->firsts;
                          $last = $request->firsts;
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
         if(!empty($request->firsts))
                      {
                          $first = $request->firsts;
                          $last = $request->firsts;
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
         if(!empty($request->firsts))
                      {
                          $first = $request->firsts;
                          $last = $request->firsts;
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

           return view('home',['project' => $project, 'kelompok' => $kelompok, 'daftar' => $daftar ]);
       }



    public function filtergrap($id)
    {
      $id_user = Auth::user()->id;
      $jabatan = Auth::user()->jabatan;
if($jabatan == 'karyawan'){
      $data = DB::table('pengajuan')
      ->select('nominal_pengajuan')
      ->where('status',3)
      ->where('pengajuan.id_user', $id_user);
      if($id != 0)
                   {
                       $first = $id;
                       $last = $id;
                       $tahun = date("Y");
                       $from = $tahun.'-'.$first.'-01';
                       $to = $tahun.'-'.$last.'-30';

                    $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                   }
        else {
          $first = date("m");
          $last = date("m");
          $tahun = date("Y");
          $from = $tahun.'-'.$first.'-01';
          $to = $tahun.'-'.$last.'-30';

       $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);
        }
            $data = $data->get();

            $array = array();
            foreach ($data as $key) {
              $array[] = number_format($key->nominal_pengajuan);

            }


  }else {

    $data = DB::table('pengajuan')
    ->select('nominal_pengajuan')
    ->where('status',3);
    if($id != 0)
                 {
                     $first = $id;
                     $last = $id;
                     $tahun = date("Y");
                     $from = $tahun.'-'.$first.'-01';
                     $to = $tahun.'-'.$last.'-30';

                  $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                 }
      else {
        $first = date("m");
        $last = date("m");
        $tahun = date("Y");
        $from = $tahun.'-'.$first.'-01';
        $to = $tahun.'-'.$last.'-30';

     $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);
      }
          $data = $data->get();

          $array = array();
          foreach ($data as $key) {
            $array[] = number_format($key->nominal_pengajuan);

          }




  }
                return response()->json($data);



    }

    public function totalfilter($id){
      $id_user = Auth::user()->id;
      $jabatan = Auth::user()->jabatan;
if($jabatan == 'karyawan'){
      $data = DB::table('pengajuan')
      ->select('nominal_pengajuan')
      ->where('status',3)
      ->where('pengajuan.id_user', $id_user);

      if($id != 0)
                   {
                       $first = $id;
                       $last = $id;
                       $tahun = date("Y");
                       $from = $tahun.'-'.$first.'-01';
                       $to = $tahun.'-'.$last.'-30';

                    $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

                   }
        else {
          $first = date("m");
          $last = date("m");
          $tahun = date("Y");
          $from = $tahun.'-'.$first.'-01';
          $to = $tahun.'-'.$last.'-30';

       $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);
        }
            $data = $data->sum('nominal_pengajuan');

            $array = array();
            $array[] = number_format($data,0, ',' , '.');

}else {
  $data = DB::table('pengajuan')
  ->select('nominal_pengajuan')
  ->where('status',3);

  if($id != 0)
               {
                   $first = $id;
                   $last = $id;
                   $tahun = date("Y");
                   $from = $tahun.'-'.$first.'-01';
                   $to = $tahun.'-'.$last.'-30';

                $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);

               }
    else {
      $first = date("m");
      $last = date("m");
      $tahun = date("Y");
      $from = $tahun.'-'.$first.'-01';
      $to = $tahun.'-'.$last.'-30';

   $data = $data->whereBetween('pengajuan.created_at',[$from,$to]);
    }
        $data = $data->sum('nominal_pengajuan');

        $array = array();
        $array[] = number_format($data,0, ',' , '.');


}
            return response()->json($array);


    }

}
