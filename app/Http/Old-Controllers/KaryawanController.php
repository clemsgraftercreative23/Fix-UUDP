<?php

namespace App\Http\Controllers;

use App\User;
use App\Api;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;

class KaryawanController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

 	public function index () {
    	$karyawan = DB::select( DB::raw("SELECT * FROM users WHERE jabatan='karyawan'"));
        return view('karyawan.index',['karyawan'=>$karyawan]);
    }

    public function store() {

    	$token = Api::where('id', 1)->get()->pluck('token');
		$session = Api::where('id', 1)->get()->pluck('session');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://zeus.accurate.id/accurate/api/employee/list.do?sp.pageSize=100");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/json',
		'header' => "Authorization: Bearer ".$token['0'],
		'X-Session-ID: '.$session['0']));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		$data = json_decode($server_output, TRUE);
		$karyawan = $data['d'];
		$jabatan = 'karyawan';
		// echo "<pre>";
		// print_r($data['d']);
		// echo "</pre>";
		foreach ($karyawan as $key ) {
			if (User::where('username', '=', $key['number'])->exists()) {
					DB::table('users')
					->where('username', $key['number'])
					->update([
					'name' => $key['name'],
		            'email'=>$key['name'].'@mail.com',
		            'password'=>Hash::make('12345678'),
		            'name' => $key['name'],
		            'resignMonth' => $key['resignMonth'],
		            'departmentId' => $key['departmentId'],
		            'optLock' => $key['optLock'],
		            'joinDateView' => $key['joinDateView'],
		            'resignYear' => $key['resignYear'],
		            'bankName' => $key['bankName'],
		            'contactInfoId' => $key['contactInfoId'],
		            'startMonthPayment' => $key['startMonthPayment'],
		            'nikNo' => $key['nikNo'],
		            'addressId' => $key['addressId'],
		            'joinDate' => $key['joinDate'],
		            'salesmanUserId' => $key['salesmanUserId'],
		            'nettoIncomeBefore' => $key['nettoIncomeBefore'],
		            'pphBefore' => $key['pphBefore'],
		            'posRoleId' => $key['posRoleId'],
		            'startYearPayment' => $key['startYearPayment'],
		            'bankAccountName' => $key['bankAccountName'],
		            'employeeTaxStatus' => $key['employeeTaxStatus'],
		            'bankAccount' => $key['bankAccount'],
		            'branchId' => $key['branchId'],
		            'bankCode' => $key['bankCode'],
		            'domisiliType' => $key['domisiliType'],
		            'calculatePtkp' => $key['calculatePtkp'],
		            'pph' => $key['pph'],
		            'npwpNo' => $key['npwpNo'],
		            'suspended' => $key['suspended'],
		            'employeeWorkStatus' => $key['employeeWorkStatus'],
		            'salesman' => $key['salesman'],
		            'resign' => $key['resign'],
					]);
			} else {
				$form_data = array(
		            'name' => $key['name'],
		            'jabatan' => $jabatan,
		            'email'=>$key['name'].'@mail.com',
		            'password'=>Hash::make('12345678'),
		            'name' => $key['name'],
		            'resignMonth' => $key['resignMonth'],
		            'departmentId' => $key['departmentId'],
		            'optLock' => $key['optLock'],
		            'joinDateView' => $key['joinDateView'],
		            'resignYear' => $key['resignYear'],
		            'bankName' => $key['bankName'],
		            'contactInfoId' => $key['contactInfoId'],
		            'startMonthPayment' => $key['startMonthPayment'],
		            'nikNo' => $key['nikNo'],
		            'addressId' => $key['addressId'],
		            'username' => $key['number'],
		            'joinDate' => $key['joinDate'],
		            'salesmanUserId' => $key['salesmanUserId'],
		            'nettoIncomeBefore' => $key['nettoIncomeBefore'],
		            'pphBefore' => $key['pphBefore'],
		            'posRoleId' => $key['posRoleId'],
		            'startYearPayment' => $key['startYearPayment'],
		            'bankAccountName' => $key['bankAccountName'],
		            'employeeTaxStatus' => $key['employeeTaxStatus'],
		            'bankAccount' => $key['bankAccount'],
		            'branchId' => $key['branchId'],
		            'bankCode' => $key['bankCode'],
		            'domisiliType' => $key['domisiliType'],
		            'calculatePtkp' => $key['calculatePtkp'],
		            'pph' => $key['pph'],
		            'npwpNo' => $key['npwpNo'],
		            'suspended' => $key['suspended'],
		            'employeeWorkStatus' => $key['employeeWorkStatus'],
		            'salesman' => $key['salesman'],
		            'resign' => $key['resign'],
	        	);

				User::create($form_data);
			}
		}

		return response()->json(['success' => 'Data is successfully updated']);


    }

    public function show($id){
      $karyawan = DB::select( DB::raw("SELECT * FROM users WHERE id = $id"));
         return view('karyawan.detail',['karyawan'=>$karyawan]);
    }

}
