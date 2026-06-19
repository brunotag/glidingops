# Systematic MySQL Error Detection

## Problem

`mysqli_query()` returns `false` on failure but never throws. The codebase has ~300 call sites that pervasively don't check the return value. Failed INSERTs/UPDATEs/DELETEs silently lose data while the UI reports success. A production example: a message >255 chars was rejected by STRICT_TRANS_TABLES mode, emails went out, but nothing saved to DB, and the user saw "Sent successfully."

## Approach

Replace at the **connection level**, not the call level. A single subclass of `mysqli` intercepts `query()`, logs all failures, and throws on commands (INSERT/UPDATE/DELETE/REPLACE). Zero changes to the 300 `mysqli_query()` calls.

### Proposed Implementation

```php
// helpers/database.php
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

            error_log("[DB] FAILED in $file:$line: " . $this->error);

            if ($isCommand) {
                throw new RuntimeException("Database command failed in $file:$line: " . $this->error);
            }
        }

        return $result;
    }
}

function open_gliding_db()
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
```

### What It Detects

- Data truncation (msg too long, wrong type)
- FK violations
- Duplicate key violations
- Syntax errors
- Table/column doesn't exist
- Deadlocks, lock waits
- Connection failures

### What It Does NOT Detect

- SELECT returning 0 rows (not an error, just empty)
- UPDATE affecting 0 rows (matched no WHERE)
- Queries that succeed but return wrong data (logic bugs)

### Error Handling Policy

| Query Type | On Failure |
|------------|------------|
| INSERT/UPDATE/DELETE/REPLACE | Log + throw |
| SELECT, SET, etc. | Log + return false (existing code handles or tolerates) |

### Migration Plan

Replace all connection patterns (~105 files) from:

```php
$con_params = require('./config/database.php');
$con_params = $con_params['gliding'];
$con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);
if (mysqli_connect_errno()) { die(...); }
```

to:

```php
require_once __DIR__ . '/helpers/database.php';
$con = open_gliding_db();
```

Variant path prefixes:
- Root files: `__DIR__ . '/helpers/database.php'`
- API files: `__DIR__ . '/../helpers/database.php'`
- Maintenance: `__DIR__ . '/../helpers/database.php'`
- Orgs: `__DIR__ . '/../../helpers/database.php'`
- Tests: `__DIR__ . '/../helpers/database.php'`

### Files To Modify

~105 files total across root, api/, maintenance/, orgs/*/, includes/, private/, tests/, map/.
