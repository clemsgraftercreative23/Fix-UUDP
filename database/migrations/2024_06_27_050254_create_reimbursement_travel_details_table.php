<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReimbursementTravelDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reimbursement_travel_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reimbursement_travel_id');
            $table->string('destination');
            $table->string('currency');
            $table->string('evidence');
            $table->integer('idr_rate');
            $table->integer('amount');
            $table->integer('tax')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reimbursement_travel_details');
    }
}
