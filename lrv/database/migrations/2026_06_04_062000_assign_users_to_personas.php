<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AssignUsersToPersonas extends Migration
{
    public function up()
    {
        // Map bitmask bits to persona names
        // bit 2 -> booking, bit 4 -> daily-ops, bit 8 -> cfo
        // bit 16 -> cfi, bit 32 -> engineer, bit 64 -> admin, bit 128 -> god
        $bitPersonaMap = [
            2   => 'booking',
            4   => 'daily-ops',
            8   => 'cfo',
            16  => 'cfi',
            32  => 'engineer',
            64  => 'admin',
            128 => 'god',
        ];

        $personas = DB::table('personas')->pluck('id', 'name');

        $users = DB::table('users')
            ->whereNotNull('securitylevel')
            ->where('securitylevel', '>', 0)
            ->get(['id', 'securitylevel']);

        $inserts = [];
        foreach ($users as $user) {
            $level = (int) $user->securitylevel;
            foreach ($bitPersonaMap as $bit => $personaName) {
                if ($level & $bit) {
                    $inserts[] = [
                        'user_id'    => $user->id,
                        'persona_id' => $personas[$personaName],
                        'org_id'     => null,
                    ];
                }
            }
        }

        // Batch insert in chunks of 500
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('user_personas')->insert($chunk);
        }

        echo "Assigned " . count($inserts) . " user_persona rows for "
            . count($users) . " users with securitylevel > 0.\n";
    }

    public function down()
    {
        DB::table('user_personas')->truncate();
    }
}
