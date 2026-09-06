<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Minimal key/value store for admin-toggleable runtime settings (e.g.
     * whether the Driver reimbursement module has OCR-derived invoice
     * checking turned on) -- nothing like this existed before.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('app_settings')) {
            return;
        }

        Schema::create('app_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
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
        Schema::dropIfExists('app_settings');
    }
}
