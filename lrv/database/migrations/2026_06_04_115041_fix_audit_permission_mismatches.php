<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixAuditPermissionMismatches extends Migration
{
    public function up()
    {
        // Remove cfo-style perms from admin (admin not granted these in old bitmask)
        DB::statement("
            DELETE FROM persona_permissions
            WHERE persona_id = (SELECT id FROM personas WHERE name = 'admin')
              AND permission_id IN (SELECT id FROM permissions WHERE name IN (
                'charges.manage', 'tow-charges.manage',
                'incentive-schemes.manage', 'scheme-subs.manage'
              ))
        ");

        // Remove flights.manage from daily-ops (was admin+cfo only in old system)
        DB::statement("
            DELETE FROM persona_permissions
            WHERE persona_id = (SELECT id FROM personas WHERE name = 'daily-ops')
              AND permission_id = (SELECT id FROM permissions WHERE name = 'flights.manage')
        ");

        // Restore api.user-form and api.users to god (god had user access in old system)
        DB::statement("
            INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
            SELECT (SELECT id FROM personas WHERE name = 'god'), id FROM permissions
            WHERE name IN ('api.user-form', 'api.users')
        ");
    }

    public function down()
    {
        // Reverse: restore deleted perms to admin
        DB::statement("
            INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
            SELECT (SELECT id FROM personas WHERE name = 'admin'), id FROM permissions
            WHERE name IN ('charges.manage', 'tow-charges.manage',
                           'incentive-schemes.manage', 'scheme-subs.manage')
        ");
        // Restore flights.manage to daily-ops
        DB::statement("
            INSERT IGNORE INTO persona_permissions (persona_id, permission_id)
            SELECT (SELECT id FROM personas WHERE name = 'daily-ops'), id FROM permissions
            WHERE name = 'flights.manage'
        ");
        // Remove api.user-form and api.users from god
        DB::statement("
            DELETE FROM persona_permissions
            WHERE persona_id = (SELECT id FROM personas WHERE name = 'god')
              AND permission_id IN (SELECT id FROM permissions WHERE name IN ('api.user-form', 'api.users'))
        ");
    }
}
