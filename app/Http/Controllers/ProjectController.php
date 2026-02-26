<?php

namespace App\Http\Controllers;

use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Budgetproject;
use App\Api;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;

class ProjectController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
		$project = DB::select( DB::raw("SELECT master_project.*,COALESCE(sum(budgetproject.limit),0) as total  FROM 
							master_project LEFT JOIN budgetproject ON master_project.id=budgetproject.id_proyek group by   
							master_project.id"
					)); 		
        return view('master_project.index',['project'=>$project]);
    }

    public function store(Request $request)
    {
    	$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$url = 'https://zeus.accurate.id/accurate/api/project/save.do';
		// $data = array("no" => "Miaw 1","description" => "Miaw 2","name"=>"Miaw 3");
        $data = array(
            'no'        =>   $request->no_project,
            'name'        =>   $request->nama,
            'description'        =>   $request->keterangan
        );
		// $postdata = json_encode($data);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'header' => "Authorization: Bearer ".$token['0'],
			'X-Session-ID: '.$session['0']));
		$result = curl_exec($ch);
		curl_close($ch);
		return response()->json(['success' => 'Data is successfully saved']);
    }

     public function edit($id)
    {
        if(request()->ajax())
        {
            $data = Master_project::findOrFail($id);
            return response()->json(['data' => $data]);
        }
    }

    public function update(Request $request)
    {   

    	$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$url = 'https://zeus.accurate.id/accurate/api/project/save.do?id='.$request->hidden_id;
        $data = array(
            'no'        =>   $request->no_project,
            'name'        =>   $request->nama,
            'description'        =>   $request->keterangan,
        );
		// $postdata = json_encode($data);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'header' => "Authorization: Bearer ".$token['0'],
			'X-Session-ID: '.$session['0']));
		$result = curl_exec($ch);
		curl_close($ch);
		//INSERT ATAU UPDATE KE TABLE BADGETPROJECT
		if ($request->cekpost=='add') {
			$count = count($request->id_kelompok);
	        for ($i=0; $i < $count; $i++) {
	          $budget = new Budgetproject;
	          $budget->id_proyek = $request->idmain;
	          $budget->id_kelompok = $request->id_kelompok[$i];
	          $budget->id_daftar = $request->id_daftar[$i];
	          $budget->limit = preg_replace("/[^0-9]/", "", $request->limit[$i]);
			  $budget->save();
			  Budgetproject::whereNull('id_kelompok')->delete();
	        }
		} else if ($request->cekpost=='edit') {

			$count1 = count($request->id_kelompok_edit);
			
			DB::table('budgetproject')->where('id_proyek', $request->idmain)->delete();
			
	        for ($j=0; $j < $count1; $j++) {
	          $budget_edit = new Budgetproject;
	          $budget_edit->id_proyek = $request->idmain;
	          $budget_edit->id_kelompok = $request->id_kelompok_edit[$j];
	          $budget_edit->id_daftar = $request->id_daftar_edit[$j];
	          $budget_edit->limit = preg_replace("/[^0-9]/", "", $request->limit_edit[$j]);
			  $budget_edit->save();
	        }
		}
		//END INSERT ATAU UPDATE KE TABLE BADGETPROJECT

		//INSERT MULTIPLE
			// $count = count($request->id_kelompok)-1;
	  //       for ($i=0; $i < $count; $i++) {
	  //         $budget = new Budgetproject;
	  //         $budget->id_proyek = $request->idmain;
	  //         $budget->id_kelompok = $request->id_kelompok[$i];
	  //         $budget->id_daftar = $request->id_daftar[$i];
	  //         $budget->limit = $request->limit[$i];
			//   $budget->save();
	  //       }
	    
	  //   	//UPDATE MULTIPLE
	  //   	$idbudget = $request->idbudget;
			// $id_proyek = $request->idmain;
			// $id_kelompok = $request->id_kelompok;
			// $id_daftar = $request->id_daftar;
			// $limit = $request->limit;

			// foreach($idbudget as $k => $id){
			// $values = array(
			// 	'id_proyek' => $id_proyek,
			// 	'id_kelompok' => $id_kelompok[$k],
			// 	'id_daftar' => $id_daftar[$k],
			// 	'limit' => $limit[$k],
			// );

			// DB::table('budgetproject')->where('id','=',$id)->update($values);
			// }

		return response()->json(['success' => 'Data is successfully saved']);    
    }



    public function syncProject() {

    	//HITUNG JUMLAH HALAMAN PAGINATION
    	$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$miaw = curl_init();
		curl_setopt($miaw, CURLOPT_URL,"https://zeus.accurate.id/accurate/api/project/list.do");
		curl_setopt($miaw, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'header' => "Authorization: Bearer ".$token['0'],
			'X-Session-ID: '.$session['0']));
		curl_setopt($miaw, CURLOPT_POST, 1);
		curl_setopt($miaw, CURLOPT_POST, 1);
		curl_setopt($miaw, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($miaw);
		curl_close ($miaw);
		$data = json_decode($server_output, TRUE);
		$count =  $data['sp']['pageCount'];
		//SETELAH DAPAT HASIL COUNT, KEMUDIAN LOOPING URL ACCURATE UNTUK HALAMAN PROJECT
		for ($x = 1; $x <= $count; $x++) {
		  $urls[] = "https://zeus.accurate.id/accurate/api/project/list.do?sp.page=".$x;
		}
		$result = [];
		$response = [];
		//LALU HASILNYA INITIATE KE CURL
		foreach ($urls as $key => $url) {
		    $ch[$key] = curl_init();
		    curl_setopt($ch[$key], CURLOPT_URL,$url);
			curl_setopt($ch[$key], CURLOPT_HTTPHEADER, array(
				'Accept: application/json',
				'header' => "Authorization: Bearer ".$token['0'],
				'X-Session-ID: '.$session['0']));
			curl_setopt($ch[$key], CURLOPT_POST, 1);
			curl_setopt($ch[$key], CURLOPT_POST, 1);
			curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch[$key]);
			curl_close ($ch[$key]);
			$data = json_decode($server_output, TRUE);
			$project = $data['d'];
			$response[$url] = [];
			//INSERT ATAU UPDATE TO DATABASE
			foreach ($project as $dt ) {
				$response[$url][] = $dt['id'];

				if (Master_project::where('id_project', '=', $dt['id'])->exists()) {
					DB::table('master_project')
						->where('id_project', $dt['id'])
						->update([
						'no_project' => $dt['no'],
			            'keterangan' => $dt['description'],
			            'nama' => $dt['name'],
			            // 'id_project' => $dt['id'],
						]);
				} else {
					$form_data = array(
			            'no_project' => $dt['no'],
			            'keterangan' => $dt['description'],
			            'nama' => $dt['name'],
			            'id_project' => $dt['id'],
		        	);

					$result[] = Master_project::create($form_data);
				}
			}
			//END INSERT ATAU UPDATE TO DATABASE
		}

		//END LOOPING INITIATE KE CURL
		return response()->json(['success' => 'Data is successfully updated']);

    }

    public function destroy($id)
    {
    	
    	$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$ch = curl_init('https://zeus.accurate.id/accurate/api/project/delete.do?id='.$id);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'header' => "Authorization: Bearer ".$token['0'],
			'X-Session-ID: '.$session['0']));

		$result = curl_exec($ch);
		//HAPUS DATABASE
		DB::table('master_project')->where('id_project', $id)->delete();
		return response()->json(['success' => 'Data is successfully deleted']);
    }

    public function getProject(Request $request){
	    $id = $request->id;
	    $project = Master_project::find($id);
	    $kelompok = DB::select( DB::raw("SELECT * FROM master_kelompok_kegiatan")); 
	    $daftar = DB::select( DB::raw("SELECT * FROM master_daftar_rencana")); 
	    $cekBudget =  \DB::table('budgetproject')->where('id_proyek',$id)->count();
	    $listBudget = DB::select( DB::raw("SELECT * FROM budgetproject WHERE id_proyek = '$id'")); 
	    return view('master_project/detailproject',['id'=>$id,'kelompok'=>$kelompok,'daftar'=>$daftar,'cekBudget'=>$cekBudget,'listBudget'=>$listBudget,'project'=>$project]);
	}


    public function getKelompok($id) {
    	// $qry = master_kelompok_kegiatan::where('id', $id)->get()->pluck('id_kelompok');
    	// $id_kelompok = $qry['0'];
        $daftar = DB::table("master_daftar_rencana")->where("id_kelompok",$id)->pluck("nama","id_daftar");
        return json_encode($daftar);
    }
    
}
