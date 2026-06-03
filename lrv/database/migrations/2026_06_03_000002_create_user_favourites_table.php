<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFavouritesTable extends Migration
{
    public function up()
    {
        Schema::create('user_favourites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id');
            $table->string('href', 255);
            $table->string('label', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->index('member_id');
            $table->unique(['member_id', 'href']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_favourites');
    }
}
