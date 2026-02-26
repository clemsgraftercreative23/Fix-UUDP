<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReimbursementDriver extends Model
{

  // public $timestamps = false;
    protected $table = "reimbursement_driver";
    protected $guarded = [];


    protected $cast = [
      'date' => 'date'
    ];
}
