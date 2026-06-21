<?php
session_start();
require_once __DIR__ . '/helpers/permissions.php';
require_once __DIR__ . '/helpers/logging.php';

require_perm('logs.view');

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'php';
$lines = isset($_GET['lines']) ? min(max(intval($_GET['lines']), 50), 2000) : 200;

$logSources = [
    'php' => [
        'label' => 'PHP Error Log',
        'path' => __DIR__ . '/log/error.log'
    ],
    'apache-error' => [
        'label' => 'Apache Error',
        'path' => '/var/log/apache2/error.log'
    ],
    'apache-access' => [
        'label' => 'Apache Access',
        'path' => '/var/log/apache2/access.log'
    ]
];

$currentSource = $logSources[$tab] ?? $logSources['php'];

// Read log content
$logContent = '';
$fileSize = 0;
$fileSizeFormatted = '0 B';
$fileMtime = '';

if (file_exists($currentSource['path'])) {
    $fileSize = filesize($currentSource['path']);
    $fileSizeFormatted = $fileSize > 1048576
        ? round($fileSize / 1048576, 1) . ' MB'
        : ($fileSize > 1024 ? round($fileSize / 1024, 1) . ' KB' : $fileSize . ' B');
    $fileMtime = date('Y-m-d H:i:s', filemtime($currentSource['path']));

    $escapedPath = escapeshellarg($currentSource['path']);
    $logContent = shell_exec("tail -n $lines $escapedPath 2>/dev/null") ?? '';
    if ($logContent === '' && $lines > 0) {
        $fullContent = @file_get_contents($currentSource['path']);
        if ($fullContent !== false) {
            $allLines = explode("\n", $fullContent);
            $logContent = implode("\n", array_slice($allLines, -$lines));
        }
    }
} else {
    $logContent = '';
    $fileMtime = 'N/A';
}

// AJAX mode: return just the log content
if (isset($_GET['format']) && $_GET['format'] === 'text') {
    header('Content-Type: text/plain; charset=utf-8');
    echo $logContent;
    exit;
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { padding: 15px; background: #f5f5f5; }
        .tab-bar { margin-bottom: 15px; }
        .tab-bar .btn { margin-right: 5px; }
        .controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px; }
        .controls label { font-weight: bold; margin-right: 4px; font-size: 13px; }
        .controls select, .controls input { padding: 4px 8px; font-size: 13px; }
        .controls input[type="text"] { width: 200px; }
        .log-container { background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 4px; max-height: 80vh; overflow-y: auto; font-family: 'Consolas', 'Courier New', monospace; font-size: 12px; line-height: 1.5; white-space: pre; }
        .log-line-error { color: #f48771; }
        .log-line-warn { color: #e2b93d; }
        .log-line-fatal { color: #f44747; font-weight: bold; }
        .log-line-php-warning { color: #dcdcaa; }
        .info-bar { font-size: 12px; color: #666; margin-bottom: 10px; }
        .highlight { background: #ffff00; color: #000; }
        .badge { font-size: 11px; }
        .auto-refresh-indicator { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
        .auto-refresh-indicator.on { background: #4caf50; }
        .auto-refresh-indicator.off { background: #999; }
    </style>
</head>
<body>

<div class="container-fluid">
    <h2>Server Logs</h2>

    <!-- Tab bar -->
    <div class="tab-bar">
        <?php foreach ($logSources as $key => $source): ?>
            <a href="?tab=<?php echo $key; ?>&lines=<?php echo $lines; ?>" class="btn btn-<?php echo $tab === $key ? 'primary' : 'default'; ?> btn-sm">
                <?php echo htmlspecialchars($source['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <div class="controls">
        <div>
            <label for="line-select">Lines:</label>
            <select id="line-select" onchange="changeLines(this.value)">
                <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100</option>
                <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>200</option>
                <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500</option>
                <option value="1000" <?php echo $lines == 1000 ? 'selected' : ''; ?>>1000</option>
                <option value="2000" <?php echo $lines == 2000 ? 'selected' : ''; ?>>2000</option>
            </select>
        </div>
        <div>
            <label for="search-input">Search:</label>
            <input type="text" id="search-input" placeholder="Filter lines..." onkeyup="filterLines()">
        </div>
        <div>
            <button class="btn btn-default btn-sm" onclick="refreshLog()">Refresh</button>
        </div>
        <div>
            <label style="font-weight:normal;cursor:pointer;">
                <input type="checkbox" id="autorefresh-toggle" onchange="toggleAutoRefresh()">
                <span class="auto-refresh-indicator off" id="refresh-indicator"></span>
                Auto-refresh (5s)
            </label>
        </div>
        <div class="info-bar" style="margin:0 0 0 auto;">
            <?php echo htmlspecialchars($currentSource['path']); ?>
            &mdash; <?php echo $fileSizeFormatted; ?>
            &mdash; modified <?php echo $fileMtime; ?>
        </div>
    </div>

    <!-- Log output -->
    <div class="log-container" id="log-output"><?php echo htmlspecialchars($logContent); ?></div>
</div>

<script>
var currentTab = <?php echo json_encode($tab); ?>;
var currentLines = <?php echo $lines; ?>;
var autoRefreshTimer = null;

function changeLines(val) {
    window.location.href = '?tab=' + currentTab + '&lines=' + val;
}

function refreshLog() {
    var logEl = document.getElementById('log-output');
    logEl.textContent = 'Loading...';
    fetch('?tab=' + currentTab + '&lines=' + currentLines + '&format=text')
        .then(function(r) { return r.text(); })
        .then(function(text) {
            logEl.textContent = text;
            filterLines();
        })
        .catch(function() { logEl.textContent = 'Failed to load logs.'; });
}

function filterLines() {
    var search = document.getElementById('search-input').value.toLowerCase();
    var logEl = document.getElementById('log-output');
    var lines = logEl.textContent.split('\n');

    var html = '';
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        var display = line;
        var cls = '';
        if (line.match(/\[PHP FATAL\]|\[PHP ERROR\]|\[PHP Parse error\]/i)) {
            cls = 'log-line-fatal';
        } else if (line.match(/\[PHP Warning\]|PHP Warning:|PHP Fatal error:/i)) {
            cls = 'log-line-php-warning';
        } else if (line.match(/error|fatal|critical/i) && !cls) {
            cls = 'log-line-error';
        } else if (line.match(/warn/i)) {
            cls = 'log-line-warn';
        }

        if (search && line.toLowerCase().indexOf(search) === -1) {
            continue;
        }

        if (search && cls) {
            html += '<div class="' + cls + '">' + escapeHtml(display) + '</div>';
        } else if (search) {
            var idx = display.toLowerCase().indexOf(search);
            if (idx !== -1) {
                var before = escapeHtml(display.substring(0, idx));
                var match = escapeHtml(display.substring(idx, idx + search.length));
                var after = escapeHtml(display.substring(idx + search.length));
                html += '<div>' + before + '<span class="highlight">' + match + '</span>' + after + '</div>';
            } else {
                html += '<div>' + escapeHtml(display) + '</div>';
            }
        } else if (cls) {
            html += '<div class="' + cls + '">' + escapeHtml(display) + '</div>';
        } else {
            html += '<div>' + escapeHtml(display) + '</div>';
        }
    }
    logEl.innerHTML = html;
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function toggleAutoRefresh() {
    var cb = document.getElementById('autorefresh-toggle');
    var indicator = document.getElementById('refresh-indicator');
    if (cb.checked) {
        indicator.className = 'auto-refresh-indicator on';
        autoRefreshTimer = setInterval(refreshLog, 5000);
    } else {
        indicator.className = 'auto-refresh-indicator off';
        if (autoRefreshTimer) { clearInterval(autoRefreshTimer); autoRefreshTimer = null; }
    }
}

// Apply initial color coding
filterLines();
</script>

</body>
</html>
