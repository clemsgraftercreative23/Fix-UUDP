<?php

namespace App\Http\Controllers;


use App\Listkasbank;
use App\Services\Accurate\AccurateApiTokenClient;
use DB;

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
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $basePath = '/accurate/api/glaccount/list.do?fields=no,name,id,parentId,parentName&accountType=CASH_BANK&sp.pageSize=100';
        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data sub kas & bank dari Accurate.']], 422);
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
                $parentId = isset($item['parentId']) ? $item['parentId'] : null;
                $parentNo = is_array($parentId) && isset($parentId['no']) ? $parentId['no'] : null;

                if (empty($parentId) || empty($parentNo)) {
                    continue;
                }
                if (!isset($item['parentName']) || $item['parentName'] === 'Cash & Bank') {
                    continue;
                }
                if (!isset($item['no'])) {
                    continue;
                }

                if (Listkasbank::where('kode_kasbank', '=', $item['no'])->exists()) {
                    DB::table('listkasbank')
                        ->where('kode_kasbank', $item['no'])
                        ->update([
                            'kode_list' => $parentNo,
                            'nama_list' => isset($item['name']) ? $item['name'] : null,
                        ]);
                } else {
                    Listkasbank::create([
                        'kode_list' => $parentNo,
                        'nama_list' => isset($item['name']) ? $item['name'] : null,
                        'kode_kasbank' => $item['no'],
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Data is successfully syncroned']);
    }

    
}
