<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDutyAndDutytypesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('duty');
        Schema::dropIfExists('dutytypes');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('dutytypes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 20);
        });

        Schema::create('duty', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('org');
            $table->integer('type');
            $table->dateTime('localdate');
            $table->integer('member');
        });
    }
}
