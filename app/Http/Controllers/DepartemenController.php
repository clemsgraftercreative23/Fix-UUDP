<?php

namespace App\Http\Controllers;


use App\Departemen;
use App\Api;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;

class DepartemenController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$departemen = DB::select( DB::raw("SELECT * FROM departemen"));
        return view('departemen.index',['departemen'=>$departemen]);
    }
    
    public function syncDepartemen()
    {
		$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$miaw = curl_init();
		curl_setopt($miaw, CURLOPT_URL,"https://zeus.accurate.id/accurate/api/department/list.do");
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
		  $urls[] = "https://zeus.accurate.id/accurate/api/department/list.do";
		}
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
			$a = $data['d'];
			foreach ($a as $key ) {
		 		if (Departemen::where('id_dep', '=', $key['id'])->exists()) {
						DB::table('departemen')
						->where('id_dep', $key['id'])
						->update([
			            'nama_departemen' => $key['nameWithIndentStrip'],
						]);
				} else {
					$form_data = array(
			            'id_dep' => $key['id'],
			            'nama_departemen' => $key['nameWithIndentStrip'],
		        	);
					Departemen::create($form_data);
				}
			}
		}
		return response()->json(['success' => 'Data is successfully syncroned']);
		

    }

   
    
}
