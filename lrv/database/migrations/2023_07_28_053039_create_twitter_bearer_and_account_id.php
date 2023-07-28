<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwitterBearerAndAccountId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organisations', function($table) {
            $table->string('twitter_bearer', 120)->nullable(true);
            $table->string('twitter_account_id', 64)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organisations', function($table) {
            $table->dropColumn('twitter_account_id');            
            $table->dropColumn('twitter_bearer');
        });
    }
}
