<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTables extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
        });

        Schema::create('personas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
        });

        Schema::create('persona_permissions', function (Blueprint $table) {
            $table->integer('persona_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->primary(['persona_id', 'permission_id']);
            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        Schema::create('user_personas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('persona_id')->unsigned();
            $table->integer('org_id')->unsigned()->nullable();
            $table->unique(['user_id', 'persona_id', 'org_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_personas');
        Schema::dropIfExists('persona_permissions');
        Schema::dropIfExists('personas');
        Schema::dropIfExists('permissions');
    }
}
