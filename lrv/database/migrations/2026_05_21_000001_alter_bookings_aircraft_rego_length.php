<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterBookingsAircraftRegoLength extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN aircraft_rego VARCHAR(20) NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN aircraft_rego VARCHAR(6) NULL");
    }
}