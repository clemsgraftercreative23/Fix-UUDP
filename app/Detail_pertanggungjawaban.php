<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Detail_pertanggungjawaban extends Model
{

  // public $timestamps = false;
    protected $table = "detail_pertanggungjawaban";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
