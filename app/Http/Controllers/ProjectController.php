<?php

namespace App\Http\Controllers;

use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Budgetproject;
use App\Api;
use App\Services\Accurate\AccurateApiTokenClient;
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
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $basePath = '/accurate/api/project/list.do?sp.pageSize=100';
        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data project dari Accurate.']], 422);
        }

        $data = json_decode((string) ($firstPageResponse['body'] ?? ''), true);
        if (!is_array($data)) {
            return response()->json(['errors' => ['Response Accurate tidak valid.']], 422);
        }

        $totalPage = isset($data['sp']['pageCount']) ? (int) $data['sp']['pageCount'] : 1;
        if ($totalPage < 1) {
            $totalPage = 1;
        }

        for ($page = 1; $page <= $totalPage; $page++) {
            $pagePath = $basePath . '&sp.page=' . $page;
            $pageResponse = $client->request('GET', $pagePath);
            if (!($pageResponse['ok'] ?? false)) {
                continue;
            }

            $record = json_decode((string) ($pageResponse['body'] ?? ''), true);
            if (!isset($record['d']) || !is_array($record['d'])) {
                continue;
            }

            foreach ($record['d'] as $item) {
                if (!isset($item['id'])) {
                    continue;
                }

                if (Master_project::where('id_project', '=', $item['id'])->exists()) {
                    DB::table('master_project')
                        ->where('id_project', $item['id'])
                        ->update([
                            'no_project' => isset($item['no']) ? $item['no'] : null,
                            'keterangan' => isset($item['description']) ? $item['description'] : null,
                            'nama' => isset($item['name']) ? $item['name'] : null,
                        ]);
                } else {
                    Master_project::create([
                        'no_project' => isset($item['no']) ? $item['no'] : null,
                        'keterangan' => isset($item['description']) ? $item['description'] : null,
                        'nama' => isset($item['name']) ? $item['name'] : null,
                        'id_project' => $item['id'],
                    ]);
                }
            }
        }

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
