<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Saldoharian extends Model
{

  // public $timestamps = false;
    protected $table = "saldoharian";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
