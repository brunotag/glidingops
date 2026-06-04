<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAssignableToPersonas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->boolean('assignable')->default(true)->after('description');
        });
        DB::table('personas')->where('name', 'service-user')->update(['assignable' => false]);
    }

    public function down()
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('assignable');
        });
    }
}
