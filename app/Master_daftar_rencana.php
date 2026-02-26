<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Master_daftar_rencana extends Model
{

  // public $timestamps = false;
    protected $table = "master_daftar_rencana";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
