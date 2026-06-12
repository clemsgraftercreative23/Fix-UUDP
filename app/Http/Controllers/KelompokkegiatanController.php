<?php

namespace App\Http\Controllers;

use App\Master_kelompok_kegiatan;
use App\Services\Accurate\AccurateApiTokenClient;
use DB;

class KelompokkegiatanController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$kelompok = DB::select( DB::raw("SELECT * FROM master_kelompok_kegiatan"));
        return view('kelompok_kegiatan.index',['kelompok'=>$kelompok]);
    }
    
    public function syncKelompok()
    {
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $fields = 'no,name,id,accountType,name,parentId,parentName,namaWithIndent,noWithIndent';
        $basePath = '/accurate/api/glaccount/list.do?filter.accountType.val=COGS&fields=' . $fields . '&sp.pageSize=100';

        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data kelompok kegiatan dari Accurate.']], 422);
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
                if (!isset($item['parentName']) || $item['parentName'] !== 'KEGIATAN PROYEK') {
                    continue;
                }
                if (!isset($item['id'])) {
                    continue;
                }

                if (Master_kelompok_kegiatan::where('id_kelompok', '=', $item['id'])->exists()) {
                    DB::table('master_kelompok_kegiatan')
                        ->where('id_kelompok', $item['id'])
                        ->update([
                            'nama' => isset($item['name']) ? $item['name'] : null,
                        ]);
                } else {
                    Master_kelompok_kegiatan::create([
                        'id_kelompok' => $item['id'],
                        'nama' => isset($item['name']) ? $item['name'] : null,
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Data is successfully syncroned']);
    }

   
    
}
