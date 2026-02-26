<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReimbursementTravel extends Model
{
    //
    protected $guarded = [];

    function details() {
        return $this->hasMany('App\ReimbursementTravelDetail','reimbursement_travel_id');
    }

    function tripType() {
        return $this->belongsTo('App\TravelTripType','trip_type_id');
    }

    
    function hotelCondition() {
        return $this->belongsTo('App\TravelHotelCondition','hotel_condition_id');
    }
}
