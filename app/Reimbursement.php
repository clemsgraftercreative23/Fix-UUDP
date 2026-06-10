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

    function approvalReminders() {
      return $this->morphMany('App\ApprovalReminder', 'subject');
    }

    /**
     * Build UUDP ticket number from reimbursement type code and database id.
     * Type codes: D = driver, T = travel, E = entertainment.
     */
    public static function buildTicketNumber(string $typeCode, int $id): string
    {
        return 'UUDP-REIMBURSE-' . $typeCode . '-00' . $id;
    }

    /**
     * Display name of the reimbursement submitter for WhatsApp notifications.
     */
    public function applicantDisplayName(): string
    {
        if ($this->created_by !== null && $this->created_by !== '') {
            return (string) $this->created_by;
        }

        $submitter = $this->relationLoaded('user') ? $this->user : User::find($this->id_user);

        return $submitter ? (string) $submitter->name : '-';
    }
}
