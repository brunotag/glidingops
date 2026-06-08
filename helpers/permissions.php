<?php

function getAssignablePersonaIds($con, array $editorPermissions): array {
    $r = mysqli_query($con, "
        SELECT pp.persona_id, perm.name
        FROM persona_permissions pp
        JOIN personas p ON p.id = pp.persona_id AND p.assignable = 1
        JOIN permissions perm ON perm.id = pp.permission_id
        ORDER BY pp.persona_id
    ");
    if (!$r) return [];

    $personaPerms = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $personaPerms[intval($row['persona_id'])][] = $row['name'];
    }

    $ids = [];
    foreach ($personaPerms as $pid => $perms) {
        $canAssign = true;
        foreach ($perms as $perm) {
            if (!in_array($perm, $editorPermissions)) {
                $canAssign = false;
                break;
            }
        }
        if ($canAssign) $ids[] = $pid;
    }
    return $ids;
}

function require_auth() {
    if (isset($_SESSION['who']) && $_SESSION['who'] === 'service-user') return;
    if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
        header('Location: /Login.php');
        die("Please logon");
    }
}

function effective_permissions() {
    global $con;

    if (empty($GLOBALS['_view_as_permissions_resolved'])) {
        $GLOBALS['_view_as_permissions_resolved'] = true;

        if (!empty($_GET['as'])) {
            $userPerms = $_SESSION['permissions'] ?? [];
            if (in_array('god.view-as', $userPerms) && isset($con) && $con) {
                $esc = mysqli_real_escape_string($con, $_GET['as']);
                $r = mysqli_query($con, "SELECT perm.name FROM personas p
                    JOIN persona_permissions pp ON pp.persona_id = p.id
                    JOIN permissions perm ON perm.id = pp.permission_id
                    WHERE p.name='$esc'");
                if ($r) {
                    $GLOBALS['_view_as_permissions'] = [];
                    while ($row = mysqli_fetch_assoc($r)) {
                        $GLOBALS['_view_as_permissions'][] = $row['name'];
                    }
                }
            }
        }
    }

    if (array_key_exists('_view_as_permissions', $GLOBALS)) {
        return $GLOBALS['_view_as_permissions'];
    }
    return $_SESSION['permissions'] ?? [];
}

function has_perm($perm) {
    return in_array($perm, effective_permissions());
}

function has_any_perm(...$perms) {
    return count(array_intersect($perms, effective_permissions())) > 0;
}

function require_perm(...$required) {
    require_auth();
    $perms = effective_permissions();
    foreach ($required as $req) {
        if (in_array($req, $perms)) {
            return;
        }
    }
    http_response_code(403);
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    header('Location: /error-page.php?code=403');
    exit;
}

function load_user_permissions($con, $userId) {
    $perms = [];
    if (!$con || !$userId) {
        return $perms;
    }
    $q = "SELECT DISTINCT p.name
          FROM user_personas up
          JOIN persona_permissions pp ON pp.persona_id = up.persona_id
          JOIN permissions p ON p.id = pp.permission_id
          WHERE up.user_id = " . intval($userId);
    $result = mysqli_query($con, $q);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $perms[] = $row['name'];
        }
    }
    return $perms;
}
