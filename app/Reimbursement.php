<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reimbursement extends Model
{

  // public $timestamps = false;
    protected $table = "reimbursement";
    protected $guarded = [];

    function details() {
      return $this->hasMany('App\ReimbursementDetail','id_reimbursement');
    }

    function drivers() {
      return $this->hasMany('App\ReimbursementDriver','reimbursement_id');
    }
    
    function travels() {
      return $this->hasMany('App\ReimbursementTravel','reimbursement_id');
    }

    function entertaiments() {
      return $this->hasMany('App\ReimbursementEntertaiment','reimbursement_id');
    }

    function medicals() {
      return $this->hasMany('App\ReimbursementMedical','reimbursement_id');
    }

    function rates() {
      return $this->hasMany('App\TravelTripRate','reimbursement_id');
    }

    
    function medicalExpenses() {
      return $this->hasMany('App\ReimbursementMedicalExpense','reimbursement_id');
    }

    function project() {
      return $this->belongsTo('App\Master_project','id_project');
    }

    function user() {
      return $this->belongsTo('App\User','id_user');
    }

    function metode_data() {
      return $this->belongsTo('App\Kasbank','metode','kode_perkiraan');
    }

    
    function sumber_data() {
      return $this->belongsTo('App\Listkasbank','sumber','kode_kasbank');
    }

    function department() {
      return $this->belongsTo('App\Departemen','reimbursement_department_id');
    }
}
