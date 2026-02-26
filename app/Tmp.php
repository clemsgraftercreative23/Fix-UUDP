<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tmp extends Model
{

  // public $timestamps = false;
    protected $table = "tmp_pengajuan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
