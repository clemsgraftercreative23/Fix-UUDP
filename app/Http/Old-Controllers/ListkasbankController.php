<?php

namespace App\Http\Controllers;


use App\Listkasbank;
use App\Api;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;

class ListkasbankController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$list = DB::select( DB::raw("SELECT * FROM kasbank LEFT JOIN listkasbank ON kasbank.kode_perkiraan = listkasbank.kode_list"));
        return view('listkasbank.index',['list'=>$list]);
    }
    
    public function syncListkasbank()
    {
		$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$miaw = curl_init();
		curl_setopt($miaw, CURLOPT_URL,"https://zeus.accurate.id/accurate/api/glaccount/list.do?fields=no,name,id,parentId,parentName&accountType=CASH_BANK");
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
		  $urls[] = "https://zeus.accurate.id/accurate/api/glaccount/list.do?fields=no,name,id,parentId,parentName&accountType=CASH_BANK&sp.page=$x";
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
				
			 	if($key['parentId']!='' && $key['parentName']!='KAS & SETARA KAS') 
			 	{


			 		if (Listkasbank::where('kode_kasbank', '=', $key['no'])->exists()) {
							DB::table('listkasbank')
							->where('kode_kasbank', $key['no'])
							->update([
				            'kode_list' => $key['parentId']['no'],
				            'nama_list' => $key['name'],
							]);
					} else {
						$form_data = array(
				            'kode_list' => $key['parentId']['no'],
				            'nama_list' => $key['name'],
				            'kode_kasbank' => $key['no'],
			        	);

						Listkasbank::create($form_data);
					}
			 	}
		 	}
		}

		return response()->json(['success' => 'Data is successfully syncroned']);
    }

    
}
