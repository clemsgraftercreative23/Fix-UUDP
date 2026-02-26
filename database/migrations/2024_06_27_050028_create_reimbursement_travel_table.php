<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReimbursementTravelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reimbursement_travel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reimbursement_id');
            $table->date('date');
            $table->string('purpose');
            $table->integer('trip_type_id');
            $table->integer('hotel_condition_id');
            $table->integer('allowance');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('travel_time');
            $table->integer('total');
            $table->string('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reimbursement_travel');
    }
}
