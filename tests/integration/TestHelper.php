<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\Assert;

/**
 * Get a cached mysqli connection to the gliding database.
 */
function testDb(): mysqli
{
    static $con = null;
    if ($con === null) {
        $db = require __DIR__ . '/../../config/database.php';
        $p = $db['gliding'];
        $con = mysqli_connect($p['hostname'], $p['username'], $p['password'], $p['dbname']);
        if (!$con) throw new RuntimeException('testDb: DB connection failed');
    }
    return $con;
}

/**
 * Get fgordon's users.id.
 */
function fgordonUserId(): int
{
    static $id = null;
    if ($id === null) {
        $con = testDb();
        $r = mysqli_query($con, "SELECT id FROM users WHERE usercode='fgordon'");
        if (!$r || !$u = mysqli_fetch_assoc($r)) throw new RuntimeException('fgordon user not found');
        $id = intval($u['id']);
    }
    return $id;
}

/**
 * Get fgordon's members.id.
 */
function fgordonMemberId(): int
{
    static $id = null;
    if ($id === null) {
        $con = testDb();
        $r = mysqli_query($con, "SELECT member FROM users WHERE usercode='fgordon'");
        if (!$r || !$u = mysqli_fetch_assoc($r)) throw new RuntimeException('fgordon member not found');
        $id = intval($u['member']);
    }
    return $id;
}

/**
 * Wipe all personas for fgordon and assign only the given one(s).
 * 'member' is always included as the base level.
 */
function setPersona(string|array $personas): void
{
    $con = testDb();
    $userId = fgordonUserId();
    $names = is_array($personas) ? $personas : [$personas];
    $names[] = 'member';
    $names = array_unique($names);

    mysqli_query($con, "DELETE FROM user_personas WHERE user_id=" . $userId);
    foreach ($names as $name) {
        $esc = mysqli_real_escape_string($con, $name);
        $q = "INSERT INTO user_personas (user_id, persona_id)
              SELECT $userId, p.id FROM personas p WHERE p.name='$esc'";
        if (!mysqli_query($con, $q)) throw new RuntimeException("setPersona($name) failed");
    }
}

/**
 * Restore all personas for fgordon (call in tearDown).
 */
function restoreAllPersonas(): void
{
    $con = testDb();
    $userId = fgordonUserId();
    mysqli_query($con, "DELETE FROM user_personas WHERE user_id=" . $userId);
    $q = "INSERT INTO user_personas (user_id, persona_id)
          SELECT $userId, p.id FROM personas p";
    mysqli_query($con, $q);
}

/**
 * Get persona ID by name.
 */
function personaId(string $name): int
{
    $con = testDb();
    $esc = mysqli_real_escape_string($con, $name);
    $r = mysqli_query($con, "SELECT id FROM personas WHERE name='$esc'");
    if (!$rw = mysqli_fetch_assoc($r)) throw new RuntimeException("Persona '$name' not found");
    return intval($rw['id']);
}

/**
 * Login as fgordon with a specific persona set.
 * Wipes existing personas, assigns the given one, returns authenticated Guzzle client.
 */
function loginAsPersona(string $persona): Client
{
    setPersona($persona);
    return loginClient();
}

/**
 * Generate a unique suffix for test data (timestamp + random hex).
 */
function uniqueId(): string
{
    return 'test-' . time() . '-' . bin2hex(random_bytes(4));
}

/**
 * Assert a row exists in the database matching the given conditions.
 */
function assertRowExists(mysqli $con, string $table, array $conditions, string $message = ''): void
{
    $where = [];
    foreach ($conditions as $col => $val) {
        $esc = mysqli_real_escape_string($con, (string)$val);
        $where[] = "`$col` = '$esc'";
    }
    $sql = "SELECT 1 FROM `$table` WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $r = mysqli_query($con, $sql);
    if (!$r) throw new RuntimeException("assertRowExists query failed: " . mysqli_error($con));
    $row = mysqli_fetch_assoc($r);
    Assert::assertNotNull($row, $message ?: "Expected row in $table matching " . json_encode($conditions));
}

/**
 * Assert no row exists in the database matching the given conditions.
 */
function assertRowMissing(mysqli $con, string $table, array $conditions, string $message = ''): void
{
    $where = [];
    foreach ($conditions as $col => $val) {
        $esc = mysqli_real_escape_string($con, (string)$val);
        $where[] = "`$col` = '$esc'";
    }
    $sql = "SELECT 1 FROM `$table` WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $r = mysqli_query($con, $sql);
    if (!$r) throw new RuntimeException("assertRowMissing query failed: " . mysqli_error($con));
    $row = mysqli_fetch_assoc($r);
    Assert::assertNull($row, $message ?: "Unexpected row in $table matching " . json_encode($conditions));
}
