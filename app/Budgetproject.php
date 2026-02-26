<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Budgetproject extends Model
{

  // public $timestamps = false;
    protected $table = "budgetproject";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
