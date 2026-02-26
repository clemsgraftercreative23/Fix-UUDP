<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Finish_pertanggungjawaban extends Model
{

  // public $timestamps = false;
    protected $table = "finish_pertanggungjawaban";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
