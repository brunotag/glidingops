<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMemberPermissionsAndFavouritesAdmin extends Migration
{
    public function up()
    {
        DB::statement("
            INSERT IGNORE INTO permissions (id, name, description)
            VALUES (67, 'favourites.admin', 'Manage other members favourites')
        ");
        DB::statement("
            INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
            SELECT (SELECT id FROM personas WHERE name = 'god'), 67
        ");
        DB::statement("
            INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
            SELECT (SELECT id FROM personas WHERE name = 'member'), id FROM permissions
            WHERE name IN ('password.change', 'spots.view', 'tracking.view',
                           'my-flights.view', 'my-flights.export')
        ");
    }

    public function down()
    {
        DB::statement("DELETE FROM persona_permissions WHERE permission_id = 67");
        DB::statement("DELETE FROM permissions WHERE id = 67");
        DB::statement("
            DELETE FROM persona_permissions WHERE persona_id = (SELECT id FROM personas WHERE name = 'member')
              AND permission_id IN (SELECT id FROM permissions WHERE name IN
                ('password.change', 'spots.view', 'tracking.view', 'my-flights.view', 'my-flights.export'))
        ");
    }
}
