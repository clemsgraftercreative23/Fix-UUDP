<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameCommissionerTripTypeToExecutive extends Migration
{
    /**
     * BOD review: rename Expatriate allowance trip type label.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('travel_trip_types')) {
            return;
        }

        DB::table('travel_trip_types')
            ->where('name', 'Special Rate for commissioner USD 54')
            ->update(['name' => 'Special rate for Executive USD 54']);
    }

    /**
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('travel_trip_types')) {
            return;
        }

        DB::table('travel_trip_types')
            ->where('name', 'Special rate for Executive USD 54')
            ->update(['name' => 'Special Rate for commissioner USD 54']);
    }
}
