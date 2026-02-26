<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReimbursementTravelDetail extends Model
{
    //
    protected $guarded = [];


    function costType() {
        return $this->belongsTo('App\TravelType','cost_type_id');
    }
}
