<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Master_project extends Model
{

  // public $timestamps = false;
    protected $table = "master_project";

    protected $guarded = [];
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }


}
