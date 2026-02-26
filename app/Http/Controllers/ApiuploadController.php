<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\APIController;
use App\Http\Resources\TodoCollection;
use App\Http\Resources\TodoResource;
use App\Pengajuan;
use App\Pertanggungjawaban;
use App\Listpengajuan;
use Auth;
use DB;

class ApiuploadController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      // $form_data = array(
      //     'deskripsi'        =>  $request,
      //     'updated_by'       => 'budi'
      // );
      //
      // Pertanggungjawaban::whereId(1)->update($form_data);


      $id_max = Pertanggungjawaban::max('id');
      // $user = Auth::user()->username;
      $id_listpengajuan = $request->id;
      $pengajuan = DB::select( DB::raw("SELECT * FROM list_pengajuan WHERE id='$id_listpengajuan'"));
      $id_subinduk = $pengajuan['0']->id_daftar;
      $daftar_rencana = DB::select( DB::raw("SELECT * FROM master_daftar_rencana WHERE id_daftar='$id_subinduk'"));
      $noWithIndent = $daftar_rencana['0']->noWithIndent;
      $pengajuan = DB::select( DB::raw("SELECT * FROM list_pengajuan WHERE id='$id_listpengajuan'"));
      $id_pengajuan = $pengajuan['0']->id_pengajuan;
      $id_kelompok = $pengajuan['0']->id_kelompok;
      $induk = DB::select( DB::raw("SELECT * FROM master_kelompok_kegiatan WHERE id_kelompok='$id_kelompok'"));
      $departemen = $induk['0']->nama;

      $images=array();
      $desc=array();
      $nominal_realisasi=str_replace(".", "", $request->nominal);
      // $departemen = $request->departemen;
      // $id_listpeng = $request->id_listpeng;
      // $id_peng = $request->id_peng;
      // $noWithIndent = $request->noWithIndent;
      $input=$request->all();

          $image = $request->image;
          $name = $request->name;

          $realImage = base64_decode($image);

          file_put_contents('images/pertanggungjawaban/'.$name, $realImage);

          echo "Image Uploaded Successfully.";


      // $files=$request->file('image');
      //         $name=rand() . '.' . $files->getClientOriginalExtension();
      //         $files->move('images/pertanggungjawaban',$name);
              DB::table('detail_pertanggungjawaban')->insert([
              'id_pertanggungjawaban' => $id_max,
              'nominal_realisasi' =>$nominal_realisasi,
              'nama_departemen' =>$departemen,
              'id_listpengajuan' =>$id_listpengajuan,
              'id_pengajuan' =>$id_pengajuan,
              'noWithIndent' =>$noWithIndent,
              'images' => $name,
              'amountType'        =>  "DEBIT",
              'status_tambahan'        =>  0,
              'subsidiaryType' => 'EMPLOYEE',
              // 'created_by'=>$user,
              // 'updated_by'=>$user,
              ]);
          // }
      // }

      $form_status = array(
          'status_pertanggungjawaban'        =>  1,
          // 'updated_by' => $user
      );
      Listpengajuan::whereId($request->id)->update($form_status);

      return response()->json(['title' => 'Data Added successfully.']);
    }


}
