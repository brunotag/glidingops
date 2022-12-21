<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteAddressAddresstypeTestyTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'address', function (Blueprint $table) {
                $table->dropForeign('fk_address_address_type1');
            }
        );

        Schema::drop('address');
        Schema::drop('address_type');
        Schema::drop('testy');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(
            'address_type', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name', 45);
            }
        );

        Schema::create(
            'address', function (Blueprint $table) {
                $table->integer('id', false);
                $table->integer('type')->index('fk_address_address_type1_idx');
                $table->string('addr1', 45);
                $table->string('addr2', 45)->nullable();
                $table->string('addr3', 45)->nullable();
                $table->string('addr4', 45)->nullable();
                $table->string('city', 45)->nullable();
                $table->string('postcode', 45)->nullable();
                $table->string('country', 45)->nullable();
                $table->primary(['id', 'type']);
            }
        );

        Schema::table(
            'address', function (Blueprint $table) {
                $table->foreign('type', 'fk_address_address_type1')->references('id')->on('address_type')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            }
        );

        Schema::create(
            'testy', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('Char10', 20)->nullable();
                $table->integer('IReq')->nullable();
                $table->integer('IntNormal')->nullable();
                $table->integer('IntCheckbox')->nullable();
                $table->decimal('DecimalVal', 5)->nullable();
                $table->string('Email', 60)->nullable();
                $table->timestamp('Date1')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('DateTimeSpecial2')->nullable();
            }
        );
    }
}
