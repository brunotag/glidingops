<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('bookingtypes');

        Schema::create('bookings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('org');
            $table->integer('member_id');
            $table->date('booking_date');
            $table->text('intention')->nullable();
            $table->string('aircraft_rego', 6)->nullable();
            $table->text('notes')->nullable();
            $table->string('google_event_id', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->boolean('deleted')->default(false);

            $table->index(['org', 'booking_date'], 'idx_org_date');
            $table->index('member_id', 'idx_member');

            $table->foreign('org', 'bookings_ibfk_1')
                  ->references('id')->on('organisations');
            $table->foreign('member_id', 'bookings_ibfk_2')
                  ->references('id')->on('members');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
