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

class JurnalController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
		$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$url = 'https://zeus.accurate.id/accurate/api/journal-voucher/save.do';
        $data = array (
		  'detailJournalVoucher' => 
		  array (
		    0 => 
		    array (
		      'accountNo' => '12005',
		      'amount' => '100000',
		      'amountType' => 'DEBIT',
		      'subsidiaryType' => 'EMPLOYEE',
		      'employeeNo' => 'E.00014',
		    ),
		    1 => 
		    array (
		      'accountNo' => '11101',
		      'amount' => '100000',
		      'amountType' => 'CREDIT',
		      'subsidiaryType' => 'EMPLOYEE',
		      'employeeNo' => 'E.00014',
		    ),
		  ),
		  'transDate' => '06/09/2020',
		  'description' => 'Test post accurate json',
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
		echo $result;
    }
}
