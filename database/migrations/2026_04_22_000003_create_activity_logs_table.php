<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('actor_name', 191)->nullable();
            $table->string('actor_role', 100)->nullable()->index();
            $table->string('module', 100)->index();
            $table->string('action', 100)->index();
            $table->string('reference_no', 191)->nullable()->index();
            $table->string('subject_type', 191)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->longText('meta_json')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['created_at', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
