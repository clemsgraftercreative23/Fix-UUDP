<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
class Pengajuan extends Model
{

  // public $timestamps = false;
    protected $table = "pengajuan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }

    protected $appends = [
      'selisih'
    ];

    public function getSelisihAttribute() {
        $selisih = DB::select(DB::raw("SELECT SUM(nominal_realisasi) as nominal FROM detail_pertanggungjawaban WHERE detail_pertanggungjawaban.id_pengajuan = ".$this->id." AND  id_pertanggungjawaban <> 0 LIMIT 1"));
        return $selisih;
    }

}
