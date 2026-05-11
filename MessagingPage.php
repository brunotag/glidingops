<?php
session_start();
$org = isset($_SESSION['org']) ? $_SESSION['org'] : 0;

if (!isset($_SESSION['security']) || !($_SESSION['security'] & 1)) {
    header('Location: Login.php');
    exit;
}

include 'helpers.php';
include 'helpers/mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    header('Content-Type: application/json; charset=utf-8');

    function sendApiError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) return false;
        http_response_code(500);
        echo json_encode(['type' => 'result', 'success' => 0, 'failed' => [], 'error' => 'Server error: ' . $errstr]);
        exit;
    }
    set_error_handler('sendApiError');

    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $fakeTwitter = isset($_POST['fakeTwitter']) ? (int)$_POST['fakeTwitter'] : 0;
    $recipientsJson = isset($_POST['recipients']) ? $_POST['recipients'] : '[]';

    if (empty($message)) {
        echo json_encode(['type' => 'result', 'success' => 0, 'failed' => [], 'error' => 'No message']);
        exit;
    }

    $recipients = json_decode($recipientsJson, true);
    if (!is_array($recipients) || count($recipients) === 0) {
        echo json_encode(['type' => 'result', 'success' => 0, 'failed' => [], 'error' => 'No recipients']);
        exit;
    }

    $con_params = require('./config/database.php');
    $con_params = $con_params['gliding'];
    $con = mysqli_connect($con_params['hostname'], $con_params['username'], $con_params['password'], $con_params['dbname']);

    if (mysqli_connect_errno()) {
        echo json_encode(['type' => 'result', 'success' => 0, 'failed' => [], 'error' => 'Database connection failed']);
        exit;
    }

    if (empty($subject)) {
        $date = new DateTime();
        $subject = 'WWGC Msg | ' . $date->format('D d M h:i A');
    }
    $senderEmail = 'servicedelivery@wwgc.co.nz';
    $senderName = '';

    if (isset($_SESSION['memberid'])) {
        $senderQuery = mysqli_query($con, "SELECT email, name FROM members WHERE id = " . intval($_SESSION['memberid']));
        if ($senderQuery && $senderRow = mysqli_fetch_array($senderQuery)) {
            if (!empty($senderRow['email']) && filter_var($senderRow['email'], FILTER_VALIDATE_EMAIL)) {
                $senderEmail = $senderRow['email'];
            }
            $senderName = !empty($senderRow['name']) ? $senderRow['name'] : '';
        }
    }

    $isBroadcast = $fakeTwitter ? 1 : 0;
    $msgSql = "INSERT INTO messages (org, create_time, msg, txt_sender_member_id, is_broadcast) VALUES ($org, NOW(), '" . mysqli_real_escape_string($con, $message) . "', " . intval($_SESSION['memberid']) . ", $isBroadcast)";
    mysqli_query($con, $msgSql);
    $msgId = mysqli_insert_id($con);

    $total = count($recipients);
    $success = 0;
    $failed = [];

    foreach ($recipients as $recipient) {
        $email = is_array($recipient) ? $recipient['email'] : $recipient;
        $name = is_array($recipient) && isset($recipient['name']) ? $recipient['name'] : '';

        try {
            $to = !empty($name) ? "$name <$email>" : $email;
            $emailBody = $message;
            if (!empty($senderName)) {
                $emailBody .= "\n\n $senderName";
            }
            $sent = Mail::SendMail($to, $subject, $emailBody, $senderEmail, 'text/plain');

            if ($sent) {
                $memberIdQuery = mysqli_query($con, "SELECT id FROM members WHERE email = '" . mysqli_real_escape_string($con, $email) . "' LIMIT 1");
                $memberId = null;
                if ($memberIdQuery && $memberRow = mysqli_fetch_array($memberIdQuery)) {
                    $memberId = $memberRow['id'];
                }

                $txtSql = "INSERT INTO texts (txt_msg_id, txt_member_id, txt_to, txt_status, txt_timestamp_sent) VALUES ($msgId, " . ($memberId ? intval($memberId) : 'NULL') . ", '" . mysqli_real_escape_string($con, $email) . "', 3, NOW())";
                mysqli_query($con, $txtSql);

                $success++;
            } else {
                $failed[] = ['email' => $email, 'reason' => 'Mail server rejected'];
            }
        } catch (Exception $e) {
            $failed[] = ['email' => $email, 'reason' => $e->getMessage()];
        } catch (Error $e) {
            $failed[] = ['email' => $email, 'reason' => $e->getMessage()];
        }
    }

    mysqli_close($con);

    echo json_encode([
        'type' => 'result',
        'success' => $success,
        'failed' => $failed,
        'total' => $total
    ]);
    exit;
}
?>
<!DOCTYPE HTML>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; }
.container { display: flex; flex-wrap: wrap; padding: 20px; gap: 20px; max-width: 1200px; margin: 0 auto; }
.panel { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; }
.left-panel { flex: 1; min-width: 300px; }
.right-panel { flex: 1; min-width: 300px; }
h1 { margin: 0 0 20px 0; font-size: 24px; color: #333; }
h2 { margin: 0 0 15px 0; font-size: 16px; color: #666; }
textarea { width: 100%; height: 150px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical; font-family: inherit; }
textarea:focus { outline: none; border-color: #4a90d9; }
.char-count { text-align: right; font-size: 12px; color: #666; margin-top: 4px; }
.char-count.warning { color: #e67e22; }
.char-count.error { color: #e74c3c; }
.checkbox-group { margin-top: 15px; }
.checkbox-group label { cursor: pointer; }
.subject-row { display: flex; align-items: center; margin-bottom: 10px; }
.subject-row input:disabled { background: #f5f5f5; color: #666; }
.subject-row input.enabled { background: white; color: #333; }
.search-group { display: flex; gap: 8px; margin-top: 15px; }
.search-group input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
.search-group button { padding: 10px 16px; background: #4a90d9; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
.search-group button:hover { background: #357abd; }
.search-group button:disabled { background: #ccc; cursor: not-allowed; }
#searchResults { position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto; z-index: 100; width: calc(100% - 50px); display: none; }
#searchResults div { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
#searchResults div:hover { background: #f0f0f0; }
#searchResults div:last-child { border-bottom: none; }
.search-wrapper { position: relative; }
.recipients-list { list-style: none; padding: 0; margin: 0; max-height: 300px; overflow-y: auto; }
.recipients-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
.recipients-list li:last-child { border-bottom: none; }
.recipients-list .email { flex: 1; word-break: break-all; }
.recipients-list .remove-btn { background: #e74c3c; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 12px; margin-left: 8px; }
.recipients-list .remove-btn:hover { background: #c0392b; }
.recipients-empty { color: #999; font-style: italic; padding: 20px; text-align: center; }
.mailing-lists { margin-top: 20px; }
.mailing-lists h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
.mailing-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
.mailing-btn { padding: 8px 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 12px; }
.mailing-btn:hover { background: #e0e0e0; }
.action-area { margin-top: 20px; text-align: center; }
.send-btn { padding: 12px 32px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
.send-btn:hover { background: #219a52; }
.send-btn:disabled { background: #ccc; cursor: not-allowed; }
.send-btn.loading { background: #f39c12; }

/* Modal styles */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
.modal.active { display: flex; }
.modal-content { background: white; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
.modal-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
.modal-body { margin-bottom: 20px; }
.modal-footer { text-align: right; }
.modal-footer button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px; }
.modal-footer .cancel-btn { background: #e0e0e0; color: #333; }
.modal-footer .confirm-btn { background: #27ae60; color: white; }
.modal-footer .confirm-btn:hover { background: #219a52; }
.recipient-preview { max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; padding: 10px; }
.recipient-preview-item { padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
.recipient-preview-item:last-child { border-bottom: none; }

/* Progress modal */
.progress-modal .modal-content { text-align: center; }
.progress-bar { width: 100%; height: 24px; background: #e0e0e0; border-radius: 12px; overflow: hidden; margin: 20px 0; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, #27ae60, #2ecc71); transition: width 0.3s ease; }
.progress-text { font-size: 14px; color: #666; margin-bottom: 10px; }
.email-status-list { max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; text-align: left; margin-top: 15px; }
.email-status-item { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 13px; display: flex; justify-content: space-between; }
.email-status-item:last-child { border-bottom: none; }
.email-status-item.success { color: #27ae60; }
.email-status-item.failed { color: #e74c3c; }
.email-status-item .email { flex: 1; word-break: break-all; }
.email-status-item .status { margin-left: 10px; white-space: nowrap; }

/* Result modal */
.result-icon { font-size: 48px; text-align: center; margin-bottom: 15px; }
.result-icon.success { color: #27ae60; }
.result-icon.failed { color: #e74c3c; }
.result-summary { text-align: center; margin-bottom: 20px; }
.result-summary .sent { color: #27ae60; font-size: 18px; font-weight: bold; }
.result-summary .failed { color: #e74c3c; font-size: 14px; margin-top: 5px; }
.failed-list { max-height: 150px; overflow-y: auto; border: 1px solid #e74c3c; border-radius: 4px; padding: 10px; margin-top: 10px; text-align: left; }
.failed-item { padding: 4px 0; font-size: 13px; color: #e74c3c; }

<?php $inc = "./orgs/" . $org . "/heading2.css"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.css"; include $inc; ?>
</style>
</head>
<body>
<?php $inc = "./orgs/" . $org . "/heading2.txt"; include $inc; ?>
<?php $inc = "./orgs/" . $org . "/menu1.txt"; include $inc; ?>

<div class="container">
<div class="panel left-panel">
<h1>New Message <a href="MessagingPageOld" style="font-size:14px;font-weight:normal;">(Old Version)</a></h1>

<div>
<div class="subject-row">
<input type="text" id="messageSubject" readonly style="width:calc(100% - 120px);padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;background:#f5f5f5;">
<label style="margin-left:10px;"><input type="checkbox" id="editSubject"> Custom subject</label>
</div>
<textarea id="messageText" placeholder="Type your message here..." maxlength="500"></textarea>
<div class="char-count"><span id="charCount">0</span>/500</div>
</div>

<div class="checkbox-group">
<label><input type="checkbox" id="fakeTwitter"> Also post to "Fake Twitter"</label>
</div>

<div class="search-wrapper">
<div class="search-group">
<input type="text" id="memberSearch" placeholder="Search members by name..." autocomplete="off">
<div id="searchResults"></div>
</div>
</div>

<div class="mailing-lists">
<h3>Mailing Lists</h3>
<div class="mailing-buttons">
<?php
$mailing_lists = [
    'WGC Committee'           => 'committee@wwgc.co.nz',
    'WGC Instructors Team'   => 'instructors@wwgc.co.nz',
    'WGC LPC Group'          => 'lpc@wwgc.co.nz',
    'WGC Members'            => 'members@wwgc.co.nz',
    'WGC Service Delivery'    => 'servicedelivery@soar.co.nz',
    'WGC Winch Group'       => 'winch@wwgc.co.nz',
    'WWGC Cable Car'        => 'cablecar@wwgc.co.nz',
    'Official Observers'     => 'official-observers@wwgc.co.nz',
];
foreach ($mailing_lists as $name => $email):
?>
<button type="button" class="mailing-btn" data-email="<?=htmlspecialchars($email)?>" title="<?=htmlspecialchars($email)?>"><?=htmlspecialchars($name)?></button>
<?php endforeach; ?>
</div>
</div>
</div>

<div class="panel right-panel">
<h2>Recipients (<span id="recipientCount">0</span>)</h2>
<ul class="recipients-list" id="recipientsList">
<li class="recipients-empty">No recipients yet</li>
</ul>

<div class="action-area">
<button type="button" class="send-btn" id="previewBtn" disabled>Preview & Send</button>
</div>
</div>
</div>

<!-- Preview Modal -->
<div class="modal" id="previewModal">
<div class="modal-content">
<div class="modal-header">Confirm Send</div>
<div class="modal-body">
<p>Message will be sent to <strong id="previewCount">0</strong> recipients:</p>
<div class="recipient-preview" id="previewList"></div>
</div>
<div class="modal-footer">
<button type="button" class="cancel-btn" id="cancelSend">Cancel</button>
<button type="button" class="confirm-btn" id="confirmSend">Send Now</button>
</div>
</div>
</div>

<!-- Progress Modal -->
<div class="modal progress-modal" id="progressModal">
<div class="modal-content">
<div class="modal-header">Sending Messages...</div>
<div class="modal-body">
<div class="progress-text" id="progressText">Preparing to send...</div>
<div class="progress-bar">
<div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
</div>
<div class="email-status-list" id="emailStatusList"></div>
</div>
</div>
</div>

<!-- Result Modal -->
<div class="modal" id="resultModal">
<div class="modal-content">
<div class="result-icon" id="resultIcon"></div>
<div class="modal-header" id="resultHeader"></div>
<div class="modal-body">
<div class="result-summary">
<div class="sent" id="resultSent"></div>
<div class="failed" id="resultFailed"></div>
</div>
<div class="failed-list" id="failedList" style="display: none;"></div>
</div>
<div class="modal-footer">
<button type="button" class="confirm-btn" id="doneBtn">Done</button>
</div>
</div>
</div>

<script>
const messageText = document.getElementById('messageText');
const messageSubject = document.getElementById('messageSubject');
const charCount = document.getElementById('charCount');
const fakeTwitter = document.getElementById('fakeTwitter');
const editSubject = document.getElementById('editSubject');
const memberSearch = document.getElementById('memberSearch');
const searchResults = document.getElementById('searchResults');
const recipientsList = document.getElementById('recipientsList');
const recipientCount = document.getElementById('recipientCount');
const previewBtn = document.getElementById('previewBtn');
const previewModal = document.getElementById('previewModal');
const previewCount = document.getElementById('previewCount');
const previewList = document.getElementById('previewList');
const cancelSend = document.getElementById('cancelSend');
const confirmSend = document.getElementById('confirmSend');
const progressModal = document.getElementById('progressModal');
const progressText = document.getElementById('progressText');
const progressBar = document.getElementById('progressBar');
const emailStatusList = document.getElementById('emailStatusList');
const resultModal = document.getElementById('resultModal');
const resultIcon = document.getElementById('resultIcon');
const resultHeader = document.getElementById('resultHeader');
const resultSent = document.getElementById('resultSent');
const resultFailed = document.getElementById('resultFailed');
const failedList = document.getElementById('failedList');
const doneBtn = document.getElementById('doneBtn');

let recipients = [];

function getDefaultSubject() {
    const now = new Date();
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const d = days[now.getDay()];
    const dd = String(now.getDate()).padStart(2, '0');
    const mon = months[now.getMonth()];
    const h = String(now.getHours());
    const m = String(now.getMinutes()).padStart(2, '0');
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return 'WWGC Msg | ' + d + ' ' + dd + ' ' + mon + ' ' + h12 + ':' + m + ' ' + ampm;
}

function updateSubjectState() {
    if (editSubject.checked) {
        messageSubject.removeAttribute('readonly');
        messageSubject.classList.remove('enabled');
        messageSubject.style.background = 'white';
        messageSubject.style.color = '#333';
        messageSubject.focus();
    } else {
        messageSubject.setAttribute('readonly', 'readonly');
        messageSubject.classList.add('enabled');
        messageSubject.style.background = '#f5f5f5';
        messageSubject.style.color = '#666';
        messageSubject.value = getDefaultSubject();
    }
}

messageSubject.value = getDefaultSubject();
editSubject.addEventListener('change', updateSubjectState);

function updateCharCount() {
    const len = messageText.value.length;
    charCount.textContent = len;
    charCount.parentElement.classList.remove('warning', 'error');
    if (len > 450) charCount.parentElement.classList.add('warning');
    if (len >= 500) charCount.parentElement.classList.add('error');
    updatePreviewBtn();
}

function updatePreviewBtn() {
    previewBtn.disabled = messageText.value.trim().length === 0 || recipients.length === 0;
}

function updateRecipientsList() {
    recipientCount.textContent = recipients.length;
    if (recipients.length === 0) {
        recipientsList.innerHTML = '<li class="recipients-empty">No recipients yet</li>';
    } else {
        recipientsList.innerHTML = recipients.map((r, i) => {
            const isGroup = r.name === r.email || !r.name;
            const display = isGroup
                ? escapeHtml(r.email)
                : `${escapeHtml(r.name)} &lt;${escapeHtml(r.email)}&gt;`;
            return `
            <li>
                <span class="email" title="${escapeHtml(r.email)}">${display}</span>
                <button type="button" class="remove-btn" data-index="${i}">Remove</button>
            </li>
        `}).join('');
        recipientsList.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                recipients.splice(parseInt(btn.dataset.index), 1);
                updateRecipientsList();
                updatePreviewBtn();
            });
        });
    }
    updatePreviewBtn();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function addRecipient(email, name = '') {
    if (recipients.some(r => r.email === email)) return;
    recipients.push({ email, name });
    updateRecipientsList();
}

messageText.addEventListener('input', updateCharCount);

// Member search
function getSessionId() {
    const match = document.cookie.match(/PHPSESSID=([^;]+)/);
    return match ? match[1] : '';
}

async function searchMembers(search) {
    const resp = await fetch(`/api/members-email?search=${encodeURIComponent(search)}`, {
        headers: {
            'X-Session-Id': getSessionId(),
            'X-Member-Id': '<?php echo intval($_SESSION['memberid']); ?>',
            'X-Org': '<?php echo intval($_SESSION['org']); ?>'
        }
    });
    if (!resp.ok) {
        const text = await resp.text();
        console.log('Error response:', text);
        searchResults.innerHTML = '<div>Search error: ' + resp.status + '</div>';
        searchResults.style.display = 'block';
        return [];
    }
    return resp.json();
}

let searchTimeout;
memberSearch.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchResults.style.display = 'none';
    const search = memberSearch.value.trim();
    if (search.length < 2) return;
    searchTimeout = setTimeout(async () => {
        try {
            const data = await searchMembers(search);
            if (data.length === 0) {
                searchResults.innerHTML = '<div>No members found</div>';
            } else {
                searchResults.innerHTML = data.map(m =>
                    `<div data-email="${escapeHtml(m.email)}" data-name="${escapeHtml(m.name)}" title="${escapeHtml(m.email)}">${escapeHtml(m.name)}<br><small>${escapeHtml(m.email)}</small></div>`
                ).join('');
            }
            searchResults.style.display = 'block';
        } catch (e) {
            console.error('Search error:', e);
        }
    }, 300);
});

searchResults.addEventListener('click', (e) => {
    const div = e.target.closest('div[data-email]');
    if (div) {
        addRecipient(div.dataset.email, div.dataset.name);
        memberSearch.value = '';
        searchResults.style.display = 'none';
    }
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper')) {
        searchResults.style.display = 'none';
    }
});

// Mailing list buttons
document.querySelectorAll('.mailing-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        addRecipient(btn.dataset.email, btn.dataset.email);
    });
});

// Preview modal
previewBtn.addEventListener('click', () => {
    previewCount.textContent = recipients.length;
    previewList.innerHTML = recipients.map(r => {
        const isGroup = r.name === r.email || !r.name;
        const display = isGroup
            ? escapeHtml(r.email)
            : `${escapeHtml(r.name)} &lt;${escapeHtml(r.email)}&gt;`;
        return `<div class="recipient-preview-item" title="${escapeHtml(r.email)}">${display}</div>`;
    }).join('');
    previewModal.classList.add('active');
});

cancelSend.addEventListener('click', () => {
    previewModal.classList.remove('active');
});

doneBtn.addEventListener('click', () => {
    resultModal.classList.remove('active');
    messageText.value = '';
    fakeTwitter.checked = false;
    editSubject.checked = false;
    updateSubjectState();
    recipients = [];
    updateRecipientsList();
    updateCharCount();
});

confirmSend.addEventListener('click', () => {
    previewModal.classList.remove('active');
    sendMessages();
});

async function sendMessages() {
    progressModal.classList.add('active');
    progressText.textContent = 'Sending...';
    progressBar.style.width = '50%';
    emailStatusList.innerHTML = '';

    const message = messageText.value.trim();
    const subject = messageSubject.value.trim();
    const postToFakeTwitter = fakeTwitter.checked ? 1 : 0;

    try {
        const response = await fetch('/MessagingPage.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'send',
                message: message,
                subject: subject,
                fakeTwitter: postToFakeTwitter,
                recipients: JSON.stringify(recipients)
            })
        });

        progressBar.style.width = '90%';
        progressText.textContent = 'Processing response...';

        if (!response.ok) {
            const text = await response.text();
            throw new Error('Server error ' + response.status + ': ' + text);
        }

        let data;
        const responseText = await response.text();
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 500));
        }

        progressModal.classList.remove('active');
        showResults(data);

    } catch (e) {
        progressModal.classList.remove('active');
        console.error('Send error:', e);
        alert('Error: ' + e.message);
    }
}

function showResults(data) {
    if (!data) {
        resultIcon.textContent = '(!)';
        resultIcon.className = 'result-icon failed';
        resultHeader.textContent = 'Send Completed with Errors';
        resultSent.textContent = '';
        resultFailed.textContent = 'An unexpected error occurred';
        failedList.style.display = 'none';
    } else if (data.failed.length === 0) {
        resultIcon.textContent = '(^_^)';
        resultIcon.className = 'result-icon success';
        resultHeader.textContent = 'Message Sent Successfully!';
        resultSent.textContent = `Sent to ${data.success} recipient${data.success !== 1 ? 's' : ''}`;
        resultFailed.textContent = '';
        failedList.style.display = 'none';
    } else {
        resultIcon.textContent = '(!)';
        resultIcon.className = 'result-icon failed';
        resultHeader.textContent = 'Send Completed';
        resultSent.textContent = `Sent to ${data.success} recipient${data.success !== 1 ? 's' : ''}`;
        resultFailed.textContent = `${data.failed.length} failed`;
        failedList.innerHTML = data.failed.map(f =>
            `<div class="failed-item">${escapeHtml(f.email)}: ${escapeHtml(f.reason)}</div>`
        ).join('');
        failedList.style.display = 'block';
    }
    resultModal.classList.add('active');
}
</script>
</body>
</html>