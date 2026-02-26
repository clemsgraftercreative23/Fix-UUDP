<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Master_kelompok_kegiatan extends Model
{

  // public $timestamps = false;
    protected $table = "master_kelompok_kegiatan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
