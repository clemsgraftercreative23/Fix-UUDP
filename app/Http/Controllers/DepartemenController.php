<?php

namespace App\Http\Controllers;


use App\Departemen;
use App\Services\Accurate\AccurateApiTokenClient;
use DB;

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
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $basePath = '/accurate/api/department/list.do?sp.pageSize=100';
        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data departemen dari Accurate.']], 422);
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

                if (Departemen::where('id_dep', '=', $item['id'])->exists()) {
                    DB::table('departemen')
                        ->where('id_dep', $item['id'])
                        ->update([
                            'nama_departemen' => isset($item['nameWithIndentStrip']) ? $item['nameWithIndentStrip'] : null,
                        ]);
                } else {
                    Departemen::create([
                        'id_dep' => $item['id'],
                        'nama_departemen' => isset($item['nameWithIndentStrip']) ? $item['nameWithIndentStrip'] : null,
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Data is successfully syncroned']);
    }

   
    
}
