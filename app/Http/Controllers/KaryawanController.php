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
use Ixudra\Curl\Facades\Curl;

class KaryawanController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

 	public function index () {
    	$karyawan = DB::select( DB::raw("SELECT * FROM users WHERE jabatan!='superadmin'"));
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

		$totalPage = $data['sp']['pageCount'];
		$listUrl = [];
		$listData = [];
		for($i = 1; $i <= $totalPage;$i++) {
			$baseUrl = "https://zeus.accurate.id/accurate/api/employee/list.do?sp.pageSize=100&sp.page=".$i;
			$record = Curl::to($baseUrl)
                    ->withHeaders([
                        'Accept: application/json',
						'X-Session-ID: '.$session[0],
						'Authorization: Bearer '.$token[0]
                    ])
                    ->get();
			$listUrl[] = $baseUrl;
			$record = json_decode($record);
			$listData = array_merge($listData, $record->d);
		}
		$karyawan = json_decode(json_encode($listData), true);
		$jabatan = 'karyawan';
		// echo "<pre>";
		// print_r($data['d']);
		// echo "</pre>";
		foreach ($karyawan as $item ) {
			$key = Curl::to("https://zeus.accurate.id/accurate/api/employee/detail.do?id=".$item['id'])
                ->withHeaders([
                    // 'Content-Type: application/x-www-form-urlencoded',
                    // 'Accept: application/json',
					'X-Session-ID: '.$session[0],
					'Authorization: Bearer '.$token[0]
				])->get();
			$key = json_decode(json_encode(json_decode($key)))->d;

			$key = collect($key)->toArray();
			if (User::where('idKaryawan', '=', $key['number'])->exists()) {
					User::where('idKaryawan', $key['number'])
					->update([
					'name' => $key['name'],
					'email' => $key['email'] == "" ? null : $key['email'] ,
		            'phoneNumber' => isset($key['mobilePhone']) ? $key['mobilePhone'] : null,
		            'resignMonth' => isset($key['resignMonth']) ? $key['resignMonth'] : null,
		            'departmentId' => isset($key['departmentId']) ? $key['departmentId'] : null,
		            'joinDateView' => isset($key['joinDateView']) ? $key['joinDateView'] : null,
		            'resignYear' => isset($key['resignYear']) ? $key['resignYear'] : null,
		            'bankName' => isset($key['bankName']) ? $key['bankName'] : null,
		            //'idKaryawan' => isset($key['number']) ? $key['number'] : null,
		            'contactInfoId' => isset($key['contactInfoId']) ? $key['contactInfoId'] : null,
		            'startMonthPayment' => isset($key['startMonthPayment']) ? $key['startMonthPayment'] : null,
		            'nikNo' => isset($key['nikNo']) ? $key['nikNo'] : null,
		            'addressId' => isset($key['addressId']) ? $key['addressId'] : null,
		            'joinDate' => isset($key['joinDate']) ? $key['joinDate'] : null,
		            'salesmanUserId' => isset($key['salesmanUserId']) ? $key['salesmanUserId'] : null,
		            'nettoIncomeBefore' => isset($key['nettoIncomeBefore']) ? $key['nettoIncomeBefore'] : null,
		            'pphBefore' => isset($key['pphBefore']) ? $key['pphBefore'] : null,
		            'posRoleId' => isset($key['posRoleId']) ? $key['posRoleId'] : null,
		            'startYearPayment' => isset($key['startYearPayment']) ? $key['startYearPayment'] : null,
		            'bankAccountName' => isset($key['bankAccountName']) ? $key['bankAccountName'] : null,
		            'employeeTaxStatus' => isset($key['employeeTaxStatus']) ? $key['employeeTaxStatus'] : null,
		            'bankAccount' => isset($key['bankAccount']) ? $key['bankAccount'] : null,
		            'branchId' => isset($key['branchId']) ? $key['branchId'] : null,
		            'bankCode' => isset($key['bankCode']) ? $key['bankCode'] : null,
		            'domisiliType' => isset($key['domisiliType']) ? $key['domisiliType'] : null,
		            'calculatePtkp' => isset($key['calculatePtkp']) ? $key['calculatePtkp'] : null,
		            'pph' => isset($key['pph']) ? $key['pph'] : null,
		            'npwpNo' => isset($key['npwpNo']) ? $key['npwpNo'] : null,
		            'suspended' => isset($key['suspended']) ? $key['suspended'] : null,
		            'employeeWorkStatus' => isset($key['employeeWorkStatus']) ? $key['employeeWorkStatus'] : null,
		            'salesman' => isset($key['salesman']) ? $key['salesman'] : null,
		            'resign' => isset($key['resign']) ? $key['resign'] : null,
					]);
			} else {
				$form_data = array(
		            'name' => isset($key['name']) ? $key['name'] : null,
		            'jabatan' => $jabatan,
		            'email'=>isset($key['email']) ? $key['email'] : null,
		            'phoneNumber' => isset($key['mobilePhone']) ? $key['mobilePhone'] : null,
		            'password'=>Hash::make('12345678'),
		            'name' => isset($key['name']) ? $key['name'] : null,
		            'resignMonth' => isset($key['resignMonth']) ? $key['resignMonth'] : null,
		            'departmentId' => isset($key['departmentId']) ? $key['departmentId'] : null,
		            'optLock' => 0,
		            'joinDateView' => isset($key['joinDateView']) ? $key['joinDateView'] : null,
		            'resignYear' => isset($key['resignYear']) ? $key['resignYear'] : null,
		            'bankName' => isset($key['bankName']) ? $key['bankName'] : null,
		            'contactInfoId' => isset($key['contactInfoId']) ? $key['contactInfoId'] : null,
		            'startMonthPayment' => isset($key['startMonthPayment']) ? $key['startMonthPayment'] : null,
		            'nikNo' => isset($key['nikNo']) ? $key['nikNo'] : null,
		            'addressId' => isset($key['addressId']) ? $key['addressId'] : null,
		            'username' => isset($key['number']) ? $key['number'] : null,
		            'idKaryawan' => isset($key['number']) ? $key['number'] : null,
		            'joinDate' => isset($key['joinDate']) ? $key['joinDate'] : null,
		            'salesmanUserId' => isset($key['salesmanUserId']) ? $key['salesmanUserId'] : null,
		            'nettoIncomeBefore' => isset($key['nettoIncomeBefore']) ? $key['nettoIncomeBefore'] : null,
		            'pphBefore' => isset($key['pphBefore']) ? $key['pphBefore'] : null,
		            'posRoleId' => isset($key['posRoleId']) ? $key['posRoleId'] : null,
		            'startYearPayment' => isset($key['startYearPayment']) ? $key['startYearPayment'] : null,
		            'bankAccountName' => isset($key['bankAccountName']) ? $key['bankAccountName'] : null,
		            'employeeTaxStatus' => isset($key['employeeTaxStatus']) ? $key['employeeTaxStatus'] : null,
		            'bankAccount' => isset($key['bankAccount']) ? $key['bankAccount'] : null,
		            'branchId' => isset($key['branchId']) ? $key['branchId'] : null,
		            'bankCode' => isset($key['bankCode']) ? $key['bankCode'] : null,
		            'domisiliType' => isset($key['domisiliType']) ? $key['domisiliType'] : null,
		            'calculatePtkp' => isset($key['calculatePtkp']) ? $key['calculatePtkp'] : null,
		            'pph' => isset($key['pph']) ? $key['pph'] : null,
		            'npwpNo' => isset($key['npwpNo']) ? $key['npwpNo'] : null,
		            'suspended' => isset($key['suspended']) ? $key['suspended'] : null,
		            'employeeWorkStatus' => isset($key['employeeWorkStatus']) ? $key['employeeWorkStatus'] : null,
		            'salesman' => isset($key['salesman']) ? $key['salesman'] : null,
		            'resign' => isset($key['resign']) ? $key['resign'] : null,
	        	);

				User::create($form_data);
			}
		}

		return response()->json(['success' => 'Data is successfully updated']);
    }

    public function update(Request $request)
    {   
		$data = [
			'username' => $request->username,
			'name' => $request->name,
			'nik' => $request->nik,
			'email' => $request->email,
			'bankAccount' => $request->bankAccount,
			'phoneNumber' => $request->phoneNumber,
			'bankName' => $request->bankName,
			'npwpNo' => $request->npwpNo,
			'departmentId' => $request->departmentId,
			'vehicleNo' => $request->vehicleNo,
		];

		if($request->password != "" && $request->password != null) {
			$data['password'] = Hash::make($request->password);
			$data['status_password'] = 1;
		}

        User::whereId($request->id)->update($data);
		return redirect()->back()->with(['success' => 'Data is successfully saved']);
		// return response()->json(['success' => 'Data is successfully saved']);    
    }
    
    public function updateKaryawan(Request $request)
    {   
        $nama_approval = DB::select( DB::raw("SELECT name FROM users WHERE id='$request->id_approval'"))['0']->name;
		
		if($request->password != "") {
		    
		    $data = [
    			'id' => $request->id,
    			'id_approval' => $request->id_approval,
    			'password' => Hash::make($request->password),
    			'nama_approval' => $nama_approval,
    			'departmentId' => $request->departmentId,
    			'vehicleNo' => $request->vehicleNo,
    			
    		];
    		
		} else {
		    
		    $data = [
    			'id' => $request->id,
    			'id_approval' => $request->id_approval,
    			'nama_approval' => $nama_approval,
    			'departmentId' => $request->departmentId,
    			'vehicleNo' => $request->vehicleNo,
    		];
    		
		}

        User::whereId($request->id)->update($data);
		return redirect('karyawan')->with(['success' => 'Profile Berhasil disimpan']);
    }

    public function show($id){
        $karyawan = DB::select( DB::raw("SELECT *,users.id AS id FROM users LEFT JOIN departemen ON users.departmentId = departemen.id WHERE users.id = $id"));
        $approval = DB::select( DB::raw("SELECT * FROM users WHERE jabatan='Direktur Operasional'"));
        $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY nama_departemen ASC"));
        
        return view('karyawan.detail',['karyawan'=>$karyawan, 'approval' => $approval, 'departemen' => $departemen]);
    }
    public function pro(){

      $id_user = Auth::user()->id;
      $karyawan = DB::select( DB::raw("SELECT * FROM users WHERE id = '$id_user'"));
         return view('karyawan.detail',['karyawan'=>$karyawan]);
    }

	function profile(Request $request) {
		$karyawan = \App\User::where('id',auth()->user()->id)->get();
		return view('karyawan.profile',[
			'karyawan' => $karyawan
		]);
	}

	public function deleteKaryawan(Request $request) {
        $id = $request->id_delete;
        $delete  = DB::select( DB::raw("DELETE FROM users WHERE id='$id'"));
        return response()->json(['status' => 'success']);
    }

}
