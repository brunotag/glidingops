<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteAirspacecoordsAirspaceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'airspacecoords', function (Blueprint $table) {
                $table->dropForeign('airspacecoords_ibfk_1');
            }
        );
        Schema::drop('airspacecoords');
        Schema::drop('airspace');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(
            'airspace', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name', 20)->nullable();
                $table->string('region', 20)->nullable();
                $table->string('type', 10)->nullable();
                $table->string('class', 10)->nullable();
                $table->integer('upper_height')->nullable();
                $table->integer('Lower_height')->nullable();
            }
        );

        Schema::create(
            'airspacecoords', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('airspace')->nullable()->index('idx_airspace');
                $table->integer('seq')->nullable();
                $table->string('type', 6)->nullable();
                $table->float('lattitude', 10, 0)->nullable();
                $table->float('longitude', 10, 0)->nullable();
                $table->float('arclat', 10, 0)->nullable();
                $table->float('arclon', 10, 0)->nullable();
                $table->float('arcdist', 10, 0)->nullable();
            }
        );

        Schema::table(
            'airspacecoords', function (Blueprint $table) {
                $table->foreign('airspace', 'airspacecoords_ibfk_1')->references('id')->on('airspace')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            }
        );
    }
}
