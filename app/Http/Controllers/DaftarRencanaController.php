<?php

namespace App\Http\Controllers;

use App\Master_daftar_rencana;
use App\Services\Accurate\AccurateApiTokenClient;
use DB;

class DaftarRencanaController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$rencana = DB::select( DB::raw("SELECT *,master_daftar_rencana.nama AS nama_rencana, master_kelompok_kegiatan.nama AS nama_kelompok FROM master_daftar_rencana LEFT JOIN master_kelompok_kegiatan ON master_daftar_rencana.id_kelompok = master_kelompok_kegiatan.id_kelompok"));
        return view('rencana_kegiatan.index',['rencana'=>$rencana]);
    }
    
    public function syncRencana()
    {
        $client = new AccurateApiTokenClient();
        if (!$client->isConfigured()) {
            return response()->json(['errors' => $client->configurationErrorMessages()], 422);
        }

        $fields = 'no,name,id,accountType,name,parentId,parentName,namaWithIndent,noWithIndent';
        $basePath = '/accurate/api/glaccount/list.do?filter.accountType.val=COGS&fields=' . $fields . '&sp.pageSize=100';

        $firstPageResponse = $client->request('GET', $basePath);
        if (!($firstPageResponse['ok'] ?? false)) {
            return response()->json(['errors' => ['Gagal mengambil data sub activity dari Accurate.']], 422);
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
                $parentKelompokId = is_array($parentId) && isset($parentId['id']) ? $parentId['id'] : null;

                if (empty($parentId) || empty($parentKelompokId)) {
                    continue;
                }
                if (!isset($item['parentName']) || $item['parentName'] === 'KEGIATAN PROYEK') {
                    continue;
                }
                if (!isset($item['id'])) {
                    continue;
                }

                $noWithIndent = isset($item['noWithIndent']) ? preg_replace('/[^0-9]/', '', $item['noWithIndent']) : null;

                if (Master_daftar_rencana::where('id_daftar', '=', $item['id'])->exists()) {
                    DB::table('master_daftar_rencana')
                        ->where('id_daftar', $item['id'])
                        ->update([
                            'id_kelompok' => $parentKelompokId,
                            'nama' => isset($item['name']) ? $item['name'] : null,
                            'noWithIndent' => $noWithIndent,
                        ]);
                } else {
                    Master_daftar_rencana::create([
                        'id_kelompok' => $parentKelompokId,
                        'nama' => isset($item['name']) ? $item['name'] : null,
                        'id_daftar' => $item['id'],
                        'noWithIndent' => $noWithIndent,
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Data is successfully syncroned']);
    }

    
}
