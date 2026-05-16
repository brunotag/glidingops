<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMagicLinkTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('magic_link_tokens', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('token', 64);
            $table->dateTime('created_at');
            $table->dateTime('used_at')->nullable();

            $table->unique('token', 'idx_token');
            $table->index('user_id', 'idx_user_id');
            $table->foreign('user_id', 'magic_link_tokens_ibfk_1')
                  ->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('magic_link_tokens');
    }
}
