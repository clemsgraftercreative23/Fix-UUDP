<?php

namespace App\Http\Controllers;


use App\Kasbank;
use App\Services\Accurate\AccurateApiTokenClient;
use DB;

class KasbankController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$kasbank = DB::select( DB::raw("SELECT * FROM kasbank"));
        return view('kasbank.index',['kasbank'=>$kasbank]);
    	
    }
    
    public function syncKasbank()
    {
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $basePath = '/accurate/api/glaccount/list.do?fields=no,name,id,parentId,parentName&accountType=CASH_BANK&sp.pageSize=100';
        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data kas & bank dari Accurate.']], 422);
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
                if (!isset($item['parentName']) || $item['parentName'] !== 'Cash & Bank') {
                    continue;
                }
                if (!isset($item['id'])) {
                    continue;
                }

                if (Kasbank::where('kode', '=', $item['id'])->exists()) {
                    DB::table('kasbank')
                        ->where('kode', $item['id'])
                        ->update([
                            'nama' => isset($item['name']) ? $item['name'] : null,
                            'kode_perkiraan' => isset($item['no']) ? $item['no'] : null,
                        ]);
                } else {
                    Kasbank::create([
                        'kode' => $item['id'],
                        'nama' => isset($item['name']) ? $item['name'] : null,
                        'kode_perkiraan' => isset($item['no']) ? $item['no'] : null,
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Data is successfully syncroned']);
    }

   
    
}
