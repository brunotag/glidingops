<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('user_providers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('provider', 20);
            $table->string('provider_id', 255);
            $table->dateTime('created_at');
            $table->dateTime('last_login')->nullable();

            $table->unique(['provider', 'provider_id'], 'uq_provider');
            $table->index('user_id', 'idx_user_id');
            $table->foreign('user_id', 'user_providers_ibfk_1')
                  ->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_providers');
    }
}
