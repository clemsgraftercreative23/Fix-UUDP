<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pencairan extends Model
{

  // public $timestamps = false;
    protected $table = "pencairan";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
