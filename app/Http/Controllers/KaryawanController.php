<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;
use App\Services\Accurate\AccurateApiTokenClient;

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
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $firstPageResponse = $client->request('GET', '/accurate/api/employee/list.do?sp.pageSize=100');
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data karyawan dari Accurate.']], 422);
        }

        $data = json_decode((string) ($firstPageResponse['body'] ?? ''), true);
        $totalPage = isset($data['sp']['pageCount']) ? (int) $data['sp']['pageCount'] : 1;
        if ($totalPage < 1) {
            $totalPage = 1;
        }

		$listUrl = [];
		$listData = [];
		for($i = 1; $i <= $totalPage;$i++) {
			$baseUrl = "/accurate/api/employee/list.do?sp.pageSize=100&sp.page=".$i;
			$recordResponse = $client->request('GET', $baseUrl);
            if (!($recordResponse['ok'] ?? false)) {
                continue;
            }
			$listUrl[] = $baseUrl;
			$record = json_decode((string) ($recordResponse['body'] ?? ''), true);
            if (isset($record['d']) && is_array($record['d'])) {
			    $listData = array_merge($listData, $record['d']);
            }
		}
		$karyawan = json_decode(json_encode($listData), true);
		$jabatan = 'karyawan';
		// echo "<pre>";
		// print_r($data['d']);
		// echo "</pre>";
        $syncErrors = [];
        $syncedCount = 0;

		foreach ($karyawan as $item ) {
            if (!isset($item['id'])) {
                continue;
            }
			$detailResponse = $client->request('GET', '/accurate/api/employee/detail.do?id='.$item['id']);
            if (!($detailResponse['ok'] ?? false)) {
                continue;
            }
			$key = json_decode((string) ($detailResponse['body'] ?? ''), true);
            $key = isset($key['d']) && is_array($key['d']) ? $key['d'] : [];
            if (empty($key) || empty($key['number'])) {
                continue;
            }

            $employeeLabel = $key['number'] . (isset($key['name']) ? ' (' . $key['name'] . ')' : '');

            try {
                $fields = $this->mapAccurateEmployeeFields($key, $jabatan);
                $existingUser = User::where('idKaryawan', '=', $key['number'])->first();

                if ($existingUser) {
                    unset($fields['password'], $fields['jabatan'], $fields['username'], $fields['idKaryawan'], $fields['optLock']);
                    $existingUser->update($fields);
                } else {
                    User::create($fields);
                }
                $syncedCount++;
            } catch (\Throwable $exception) {
                $syncErrors[] = $this->formatSyncError($employeeLabel, $exception);
            }
		}

        if ($syncedCount === 0 && !empty($syncErrors)) {
            return response()->json(['errors' => $syncErrors], 422);
        }

        if (!empty($syncErrors)) {
            return response()->json([
                'success' => 'Data is successfully updated',
                'warnings' => $syncErrors,
            ]);
        }

		return response()->json(['success' => 'Data is successfully updated']);
    }

    private function accurateField(array $key, $field, $default = null)
    {
        if (!array_key_exists($field, $key) || $key[$field] === null || $key[$field] === '') {
            return $default;
        }

        return $key[$field];
    }

    private function resolveSyncEmail($email, $idKaryawan)
    {
        if ($email === null || $email === '') {
            return null;
        }

        $conflictQuery = User::where('email', $email);
        if ($idKaryawan !== null && $idKaryawan !== '') {
            $conflictQuery->where('idKaryawan', '!=', $idKaryawan);
        }

        if ($conflictQuery->exists()) {
            return null;
        }

        return $email;
    }

    private function formatSyncError($employeeLabel, \Throwable $exception)
    {
        $message = $exception->getMessage();
        if (strpos($message, 'users_email_unique') !== false) {
            return 'Karyawan ' . $employeeLabel . ': email sudah digunakan akun lain, data disimpan tanpa email.';
        }

        return 'Gagal sinkron karyawan ' . $employeeLabel . '.';
    }

    private function mapAccurateEmployeeFields(array $key, $jabatan = null)
    {
        $idKaryawan = $this->accurateField($key, 'number');
        $email = $this->resolveSyncEmail($this->accurateField($key, 'email'), $idKaryawan);

        $fields = [
            'name' => $this->accurateField($key, 'name', ''),
            'email' => $email,
            'phoneNumber' => $this->accurateField($key, 'mobilePhone'),
            'resignMonth' => $this->accurateField($key, 'resignMonth'),
            'departmentId' => $this->accurateField($key, 'departmentId'),
            'joinDateView' => $this->accurateField($key, 'joinDateView', '-'),
            'resignYear' => $this->accurateField($key, 'resignYear'),
            'bankName' => $this->accurateField($key, 'bankName', '-'),
            'contactInfoId' => $this->accurateField($key, 'contactInfoId', '-'),
            'startMonthPayment' => $this->accurateField($key, 'startMonthPayment', '0'),
            'nikNo' => $this->accurateField($key, 'nikNo'),
            'addressId' => $this->accurateField($key, 'addressId', '-'),
            'joinDate' => $this->accurateField($key, 'joinDate', '-'),
            'salesmanUserId' => $this->accurateField($key, 'salesmanUserId'),
            'nettoIncomeBefore' => $this->accurateField($key, 'nettoIncomeBefore', '0'),
            'pphBefore' => $this->accurateField($key, 'pphBefore', '0'),
            'posRoleId' => $this->accurateField($key, 'posRoleId'),
            'startYearPayment' => $this->accurateField($key, 'startYearPayment', '0'),
            'bankAccountName' => $this->accurateField($key, 'bankAccountName', '-'),
            'employeeTaxStatus' => $this->accurateField($key, 'employeeTaxStatus', '-'),
            'bankAccount' => $this->accurateField($key, 'bankAccount', '-'),
            'branchId' => $this->accurateField($key, 'branchId', ''),
            'bankCode' => $this->accurateField($key, 'bankCode', '-'),
            'domisiliType' => $this->accurateField($key, 'domisiliType', '-'),
            'calculatePtkp' => $this->accurateField($key, 'calculatePtkp', '0'),
            'pph' => $this->accurateField($key, 'pph', '0'),
            'npwpNo' => $this->accurateField($key, 'npwpNo', '-'),
            'suspended' => $this->accurateField($key, 'suspended', '0'),
            'employeeWorkStatus' => $this->accurateField($key, 'employeeWorkStatus', '-'),
            'salesman' => $this->accurateField($key, 'salesman', '0'),
            'resign' => $this->accurateField($key, 'resign', '0'),
        ];

        $fields['jabatan'] = $jabatan;
        $fields['password'] = Hash::make('12345678');
        $fields['username'] = $idKaryawan;
        $fields['idKaryawan'] = $idKaryawan;
        $fields['optLock'] = $this->accurateField($key, 'optLock', '0');

        return $fields;
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

        $data = [
            'id' => $request->id,
            'id_approval' => $request->id_approval,
            'nama_approval' => $nama_approval,
            'username' => $request->username,
            'name' => $request->name,
            'nik' => $request->nik,
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
            'joinDate' => $request->joinDate,
            'bankName' => $request->bankName,
            'bankAccount' => $request->bankAccount,
            'npwpNo' => $request->npwpNo,
            'employeeWorkStatus' => $request->employeeWorkStatus,
            'departmentId' => $request->departmentId,
            'vehicleNo' => $request->vehicleNo,
        ];

		if($request->password != "") {
		    $data['password'] = Hash::make($request->password);
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
