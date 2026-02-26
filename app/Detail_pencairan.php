<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Detail_pencairan extends Model
{

  // public $timestamps = false;
    protected $table = "detail_pencairan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
