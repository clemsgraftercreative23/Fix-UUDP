<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReimbursementEntertaimentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reimbursement_entertaiments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reimbursement_id');
            $table->date('date');
            $table->string('empty_zone')->nullable();
            $table->string('attendance')->nullable();
            $table->string('position')->nullable();
            $table->string('place')->nullable();
            $table->string('guest')->nullable();
            $table->string('guest_position')->nullable();
            $table->string('company')->nullable();
            $table->string('type')->nullable();
            $table->string('amount')->nullable();
            $table->string('evidence')->nullable();
            $table->string('remark')->nullable();
            $table->string('status')->default('0');
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
        Schema::dropIfExists('reimbursement_entertaiments');
    }
}
