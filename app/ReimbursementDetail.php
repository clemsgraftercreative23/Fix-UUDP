<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReimbursementDetail extends Model
{

  // public $timestamps = false;
    protected $table = "detail_reimbursement";
    protected $guarded = [];

    function kelompok() {
      return $this->belongsTo('App\Master_kelompok_kegiatan','id_kelompok','id_kelompok');
    }
}
