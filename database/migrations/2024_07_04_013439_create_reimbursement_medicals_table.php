<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReimbursementMedicalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reimbursement_medicals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reimbursement_id');
            $table->string('status');
            $table->string('patient_name');
            $table->string('diagnose');
            $table->string('name');
            $table->string('type');
            $table->string('address')->nullable();
            $table->string('pic')->nullable();
            $table->string('phone')->nullable();
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
        Schema::dropIfExists('reimbursement_medicals');
    }
}
