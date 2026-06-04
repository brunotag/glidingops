<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateMemberPersonaAndPermissions extends Migration
{
    public function up()
    {
        DB::statement("
            INSERT IGNORE INTO permissions (id, name, description)
            VALUES (65, 'bookings.create', 'Create bookings'),
                   (66, 'bookings.admin', 'Administer bookings')
        ");

        DB::statement("
            INSERT IGNORE INTO personas (id, name, description, assignable)
            VALUES (9, 'member', 'Basic member access', 1)
        ");

        $memberPerms = [4, 7, 9, 13, 41, 65];
        foreach ($memberPerms as $permId) {
            DB::statement("
                INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
                VALUES (9, $permId)
            ");
        }

        $result = DB::select("SELECT id FROM users u
            WHERE NOT EXISTS (SELECT 1 FROM user_personas up WHERE up.user_id = u.id)");
        foreach ($result as $row) {
            DB::statement("
                INSERT IGNORE INTO user_personas (user_id, persona_id)
                VALUES ({$row->id}, 9)
            ");
        }
    }

    public function down()
    {
        DB::statement("DELETE FROM persona_permissions WHERE persona_id = 9");
        DB::statement("DELETE FROM user_personas WHERE persona_id = 9");
        DB::statement("DELETE FROM personas WHERE id = 9");
        DB::statement("DELETE FROM permissions WHERE id IN (65, 66)");
    }
}
