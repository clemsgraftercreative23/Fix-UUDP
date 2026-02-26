<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Listpengajuan extends Model
{

  // public $timestamps = false;
    protected $table = "list_pengajuan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
