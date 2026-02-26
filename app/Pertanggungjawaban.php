<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pertanggungjawaban extends Model
{

  // public $timestamps = false;
    protected $table = "pertanggungjawaban";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
