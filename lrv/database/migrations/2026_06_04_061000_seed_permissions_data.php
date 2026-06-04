<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedPermissionsData extends Migration
{
    public function up()
    {
        // --- Personas ---
        DB::table('personas')->insert([
            ['name' => 'booking',    'description' => 'Booking management'],
            ['name' => 'daily-ops',  'description' => 'Flight entry, daily operations'],
            ['name' => 'cfo',        'description' => 'Billing and treasurer reports'],
            ['name' => 'cfi',        'description' => 'Chief Flight Instructor'],
            ['name' => 'engineer',   'description' => 'Aircraft maintenance'],
            ['name' => 'admin',      'description' => 'System administration'],
            ['name' => 'god',        'description' => 'Super admin, org management'],
            ['name' => 'service-user','description' => 'Secret code -- daily sheet entry only'],
        ]);

        // --- Permissions ---
        $permRows = [
            // Page permissions
            ['name' => 'home.view',              'description' => 'Access home dashboard'],
            ['name' => 'my-flights.view',        'description' => 'View own flights'],
            ['name' => 'my-flights.export',      'description' => 'Export own flights to CSV'],
            ['name' => 'members.list',           'description' => 'List all members'],
            ['name' => 'member.edit',            'description' => 'Edit any member record'],
            ['name' => 'member.edit-self',       'description' => 'Edit own member details'],
            ['name' => 'flights.list',           'description' => 'View all flights reports'],
            ['name' => 'flights.log',            'description' => 'View daily flight log'],
            ['name' => 'bookings.view',          'description' => 'View bookings'],
            ['name' => 'password.change',        'description' => 'Change own password'],
            ['name' => 'spots.view',             'description' => 'View tracking devices'],
            ['name' => 'tracking.view',          'description' => 'View GPS tracks'],
            ['name' => 'analytics.season-trends','description' => 'View season trends analytics'],
            ['name' => 'daily-sheet.access',     'description' => 'Access daily flight sheet'],
            ['name' => 'daily-sheet.edit',       'description' => 'Edit daily sheet for any date'],
            ['name' => 'daily-sheet.start-day',  'description' => 'Start a flying day'],
            ['name' => 'self-launch.access',     'description' => 'Enter self-launch flights'],
            ['name' => 'messages.send',          'description' => 'Send broadcast messages'],
            ['name' => 'messages.view',          'description' => 'View sent messages history'],
            ['name' => 'groups.manage',          'description' => 'Manage member groups'],
            ['name' => 'billing-report.view',    'description' => 'View monthly billing report'],
            ['name' => 'treasurer-report.view',  'description' => 'View treasurer reports'],
            ['name' => 'charges.manage',         'description' => 'Manage other charges'],
            ['name' => 'tow-charges.manage',     'description' => 'Manage tow charges'],
            ['name' => 'incentive-schemes.manage','description' => 'Manage incentive schemes'],
            ['name' => 'scheme-subs.manage',     'description' => 'Manage scheme subscriptions'],
            ['name' => 'engineer.view',          'description' => 'View engineer report'],
            ['name' => 'last-flights.view',      'description' => 'View last flights list'],
            ['name' => 'users.manage',           'description' => 'Manage system users'],
            ['name' => 'users.invite',           'description' => 'Invite new users'],
            ['name' => 'audit.view',             'description' => 'View audit log'],
            ['name' => 'aircraft-types.manage',  'description' => 'Manage aircraft types'],
            ['name' => 'flight-types.manage',    'description' => 'Manage flight types'],
            ['name' => 'launch-types.manage',    'description' => 'Manage launch types'],
            ['name' => 'billing-options.manage', 'description' => 'Manage billing options'],
            ['name' => 'roles.manage',           'description' => 'Manage roles'],
            ['name' => 'membership-classes.manage','description' => 'Manage membership classes'],
            ['name' => 'membership-statuses.manage','description' => 'Manage membership statuses'],
            ['name' => 'spots.manage',           'description' => 'Manage tracking devices'],
            ['name' => 'admin.manage',           'description' => 'Admin tools like secret code, maintenance'],
            ['name' => 'analytics.dashboard',    'description' => 'View analytics dashboard'],
            ['name' => 'aircraft.manage',        'description' => 'Manage aircraft'],
            ['name' => 'flights.manage',         'description' => 'Manage all flights (edit/delete)'],
            ['name' => 'organisations.manage',   'description' => 'Manage organisations'],
            ['name' => 'god.view-as',            'description' => 'View site as another persona'],
            ['name' => 'personas.manage',        'description' => 'Manage personas'],
            ['name' => 'permissions.manage',     'description' => 'Manage permissions'],

            // API permissions
            ['name' => 'api.daily-flights',      'description' => 'GET /api/daily-flights (public with org param)'],
            ['name' => 'api.members',            'description' => 'GET /api/members'],
            ['name' => 'api.member-form',        'description' => 'GET/POST /api/member-form'],
            ['name' => 'api.member-search',      'description' => 'GET /api/member-search'],
            ['name' => 'api.members-email',      'description' => 'GET /api/members-email'],
            ['name' => 'api.aircraft',           'description' => 'GET /api/aircraft'],
            ['name' => 'api.track-flights',      'description' => 'GET /api/track-flights'],
            ['name' => 'api.favourites',         'description' => 'GET /api/favourites'],
            ['name' => 'api.myflights',          'description' => 'GET /api/myflights'],
            ['name' => 'api.flights-report',     'description' => 'GET /api/flights-report'],
            ['name' => 'api.analytics-data',     'description' => 'GET /api/analytics-data'],
            ['name' => 'api.analytics-trends',   'description' => 'GET /api/analytics-trends'],
            ['name' => 'api.date-members',       'description' => 'GET /api/date-members (dev only)'],
            ['name' => 'api.flights',            'description' => 'GET/POST /api/flights'],
            ['name' => 'api.texts',              'description' => 'GET /api/texts'],
            ['name' => 'api.users',              'description' => 'GET /api/users'],
            ['name' => 'api.user-form',          'description' => 'GET/POST /api/user-form'],
        ];

        DB::table('permissions')->insert($permRows);
        $perms = DB::table('permissions')->pluck('id', 'name');

        // --- Persona → Permission mappings ---

        // daily-ops: flight entry, messages, groups, self-launch, flight API, texts API
        $dailyOpsPerms = [
            'daily-sheet.access', 'daily-sheet.edit', 'daily-sheet.start-day',
            'self-launch.access',
            'messages.send', 'messages.view',
            'groups.manage',
            'api.flights', 'api.texts',
        ];

        // cfo: billing, treasurer, charges, tow-charges, incentive-schemes, scheme-subs
        $cfoPerms = [
            'billing-report.view', 'treasurer-report.view',
            'charges.manage', 'tow-charges.manage',
            'incentive-schemes.manage', 'scheme-subs.manage',
        ];

        // cfi: appears in combined levels only, no standalone pages
        $cfiPerms = [];

        // engineer: engineer report, last-flights, aircraft (shared)
        $engineerPerms = [
            'engineer.view', 'last-flights.view',
        ];

        // admin: users, audit, reference types, analytics, spots, admin tools, aircraft (shared)
        $adminPerms = [
            'users.manage',
            'audit.view',
            'aircraft-types.manage', 'flight-types.manage', 'launch-types.manage',
            'billing-options.manage', 'roles.manage',
            'membership-classes.manage', 'membership-statuses.manage',
            'spots.manage',
            'admin.manage',
            'analytics.dashboard',
            'api.users', 'api.user-form',
        ];

        // god: organisations, view-as, personas, permissions, invite users
        $godPerms = [
            'organisations.manage',
            'god.view-as',
            'personas.manage',
            'permissions.manage',
            'users.invite',
        ];

        // booking: currently no standalone pages — appears only in combined levels
        $bookingPerms = [];

        // service-user: daily-sheet only
        $serviceUserPerms = ['daily-sheet.access'];

        // Combined/shared permissions assigned to multiple personas
        $sharedAdminCfo = ['charges.manage', 'tow-charges.manage', 'incentive-schemes.manage', 'scheme-subs.manage'];
        $sharedAdminCfoFlights = ['flights.manage'];  // flights-list.php, flights.php
        $sharedAllAircraft = ['aircraft.manage'];  // admin OR engineer OR cfi OR cfo

        // Build the mappings
        $mapping = [
            'daily-ops'   => array_merge($dailyOpsPerms),
            'cfo'         => array_merge($cfoPerms, $sharedAdminCfo, $sharedAdminCfoFlights, $sharedAllAircraft),
            'cfi'         => array_merge($cfiPerms, $sharedAllAircraft),
            'engineer'    => array_merge($engineerPerms, $sharedAllAircraft),
            'admin'       => array_merge($adminPerms, $sharedAdminCfo, $sharedAdminCfoFlights, $sharedAllAircraft),
            'god'         => array_merge($godPerms),
            'booking'     => array_merge($bookingPerms),
            'service-user'=> array_merge($serviceUserPerms),
        ];

        // All auth-only (member) permissions — granted to every persona
        $authOnly = [
            'home.view', 'my-flights.view', 'my-flights.export',
            'members.list', 'member.edit', 'member.edit-self',
            'flights.list', 'flights.log',
            'bookings.view', 'password.change',
            'spots.view', 'tracking.view',
            'analytics.season-trends',
            'api.members', 'api.member-form', 'api.member-search',
            'api.members-email', 'api.aircraft', 'api.track-flights',
            'api.favourites', 'api.myflights', 'api.flights-report',
            'api.analytics-data', 'api.analytics-trends',
            'api.date-members',
        ];

        // Grant auth-only permissions to ALL personas
        foreach ($mapping as $personaName => $personaPerms) {
            $mapping[$personaName] = array_unique(array_merge($personaPerms, $authOnly));
        }

        // Insert persona_permissions
        $personas = DB::table('personas')->pluck('id', 'name');
        $inserts = [];
        foreach ($mapping as $personaName => $permNames) {
            $personaId = $personas[$personaName];
            foreach ($permNames as $permName) {
                if (!isset($perms[$permName])) {
                    echo "WARNING: Permission '$permName' not found — skipping\n";
                    continue;
                }
                $inserts[] = [
                    'persona_id'    => $personaId,
                    'permission_id' => $perms[$permName],
                ];
            }
        }
        DB::table('persona_permissions')->insert($inserts);

        echo "Seeded " . count($personas) . " personas, "
            . count($perms) . " permissions, "
            . count($inserts) . " persona_permission links.\n";
    }

    public function down()
    {
        DB::table('persona_permissions')->truncate();
        DB::table('personas')->truncate();
        DB::table('permissions')->truncate();
    }
}
