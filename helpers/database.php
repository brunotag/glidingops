<?php

class LoggedMySQLi extends mysqli
{
    public function query($query, $resultmode = MYSQLI_STORE_RESULT)
    {
        $result = parent::query($query, $resultmode);

        if ($result === false) {
            $type = strtoupper(substr(trim($query), 0, 6));
            $isCommand = in_array($type, ['INSERT', 'UPDATE', 'DELETE', 'REPLACE']);

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $file = basename($trace[0]['file'] ?? 'unknown');
            $line = $trace[0]['line'] ?? 0;

            error_log("[DB] FAILED in $file:$line: " . $this->error . " SQL: " . substr($query, 0, 200));

            if ($isCommand) {
                throw new RuntimeException("Database command failed in $file:$line: " . $this->error);
            }
        }

        return $result;
    }
}

function open_gliding_db(): LoggedMySQLi
{
    $con_params = require __DIR__ . '/../config/database.php';
    $g = $con_params['gliding'];
    $con = new LoggedMySQLi($g['hostname'], $g['username'], $g['password'], $g['dbname']);

    if ($con->connect_error) {
        error_log("[DB] Connection failed: " . $con->connect_error);
        throw new RuntimeException("Database connection failed: " . $con->connect_error);
    }

    return $con;
}
