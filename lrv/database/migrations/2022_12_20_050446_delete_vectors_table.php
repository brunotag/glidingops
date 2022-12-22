<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteVectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vectors', function (Blueprint $table) {
            $table->dropForeign('vectors_organisation_id_foreign');
        });
        Schema::drop('vectors');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('vectors', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('organisation_id')->index('vectors_organisation_id_foreign');
            $table->string('designation');
            $table->string('location');
            $table->unique(['designation', 'location']);
        });

        Schema::table('vectors', function (Blueprint $table) {
            $table->foreign('organisation_id')->references('id')->on('organisations')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }
}
