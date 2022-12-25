<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteDiagTable extends Migration
{
    public function up()
    {
        Schema::drop('diag');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('diag', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('create_time')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('data', 2000)->nullable();
        });
    }
}
