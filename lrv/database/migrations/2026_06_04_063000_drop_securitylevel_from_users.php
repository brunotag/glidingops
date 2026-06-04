<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropSecuritylevelFromUsers extends Migration
{
    public function up()
    {
        Schema::connection('gliding')->table('users', function (Blueprint $table) {
            $table->dropColumn('securitylevel');
        });
    }

    public function down()
    {
        Schema::connection('gliding')->table('users', function (Blueprint $table) {
            $table->integer('securitylevel')->nullable()->after('password');
        });
    }
}
