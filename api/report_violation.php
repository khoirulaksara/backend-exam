<?php
// POST /session/violation
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId']) || !isset($input['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionId or event']);
    exit;
}

$sessionId       = $input['sessionId'];
$event           = $input['event'];
$durationSeconds = isset($input['duration_seconds']) ? (int)$input['duration_seconds'] : null;
$targetApp       = isset($input['target_app']) ? substr($input['target_app'], 0, 200) : null;

// Calculate Risk Value Based on Event
$riskValue = 0;
switch ($event) {
    case 'UNPIN':
        $stmt = $pdo->prepare("SELECT COUNT(*) as unpins FROM violations WHERE session_id = ? AND event_type = 'UNPIN'");
        $stmt->execute([$sessionId]);
        $unpinCount = $stmt->fetch()['unpins'];
        $riskValue = ($unpinCount >= 1) ? 30 : 20;
        break;
    case 'DND_OFF':
        $riskValue = 25;
        break;
    case 'MULTI_WINDOW_DETECTED':
        $riskValue = 30;
        break;
    case 'APP_BACKGROUND':
        $riskValue = 20;
        break;
    case 'FOCUS_LOST':
        $riskValue = 10;
        break;
    case 'HOME_OR_RECENT_APPS':
        $riskValue = 20;
        break;
    case 'SYSTEM_DIALOG_OR_NOTIFICATION':
        $riskValue = 15;
        break;
    case 'APP_SWITCHED':
        // 1 poin per detik keluar, min 5, max 40
        $riskValue = $durationSeconds ? min(40, max(5, $durationSeconds)) : 10;
        break;
    case 'APP_SWITCHED_BRIEF':
        // Keluar singkat (<5 det): hanya logging, risiko kecil
        $riskValue = $durationSeconds ? min(5, $durationSeconds) : 2;
        break;
    case 'PERMINTAAN_KELUAR':
    case 'BATAL_KELUAR':
        $riskValue = 0;
        break;
    default:
        $riskValue = 10;
        break;
}

// Human-Readable Event Names
$humanReadableEvent = $event;
switch ($event) {
    case 'UNPIN':
        $humanReadableEvent = "Siswa mencoba membuka paksa layar ujian (Unpin)";
        break;
    case 'DND_OFF':
        $humanReadableEvent = "Siswa mematikan mode Jangan Ganggu (DND)";
        break;
    case 'MULTI_WINDOW_DETECTED':
        $humanReadableEvent = "Siswa membagi layar Android (Split Screen)";
        break;
    case 'APP_BACKGROUND':
        $humanReadableEvent = "Aplikasi ujian ditutup ke latar belakang";
        break;
    case 'FOCUS_LOST':
        $humanReadableEvent = "Layar teralihkan / Terhalang aplikasi lain";
        break;
    case 'HOME_OR_RECENT_APPS':
        $humanReadableEvent = "Siswa memencet tombol Home / Recent Apps";
        break;
    case 'SYSTEM_DIALOG_OR_NOTIFICATION':
        $humanReadableEvent = "Ada Notifikasi / Panggilan masuk merampas layar";
        break;
    case 'SALAH_TOKEN_KELUAR':
        $humanReadableEvent = "Siswa salah memasukkan Token Akses Keluar Ujian";
        break;
    case 'APP_SWITCHED':
        $appLabel = $targetApp ? " (app: $targetApp)" : "";
        $durLabel = $durationSeconds ? " selama {$durationSeconds} detik" : "";
        $humanReadableEvent = "Siswa membuka aplikasi lain{$appLabel}{$durLabel}";
        break;
    case 'APP_SWITCHED_BRIEF':
        $appLabel = $targetApp ? " (app: $targetApp)" : "";
        $durLabel = $durationSeconds ? " selama {$durationSeconds} detik" : "";
        $humanReadableEvent = "Siswa keluar sebentar{$appLabel}{$durLabel}";
        break;
}

// Log Violation
if (!$enable_strict_violations) {
    $riskValue = 0;
}
$stmt = $pdo->prepare("INSERT INTO violations (session_id, event_type, risk_value, duration_seconds, target_app) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$sessionId, $humanReadableEvent, $riskValue, $durationSeconds, $targetApp]);

// Update Session Risk Score
$pdo->prepare("UPDATE sessions SET risk_score = risk_score + ? WHERE session_id = ?")
    ->execute([$riskValue, $sessionId]);

// Check resulting risk score
$stmt = $pdo->prepare("SELECT risk_score FROM sessions WHERE session_id = ?");
$stmt->execute([$sessionId]);
$currentRisk = $stmt->fetch()['risk_score'];

// Auto-lock logic (APP_SWITCHED tidak auto-lock — overlay Android sudah handle)
$autoLockEvents = ['UNPIN', 'MULTI_WINDOW_DETECTED', 'APP_BACKGROUND', 'FOCUS_LOST', 'HOME_OR_RECENT_APPS'];
$isRecentlyUnlocked = false;
$stmtUnlock = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE session_id = ? AND event_type = 'UNLOCK_SUCCESS' AND timestamp > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
$stmtUnlock->execute([$sessionId]);
if ($stmtUnlock->fetchColumn() > 0) {
    $isRecentlyUnlocked = true;
}

if ($currentRisk >= 100) {
    $pdo->prepare("UPDATE sessions SET status = 'submitted' WHERE session_id = ?")->execute([$sessionId]);
} else if ($currentRisk >= 80 || in_array($event, $autoLockEvents)) {
    if (!($isRecentlyUnlocked && in_array($event, ['APP_BACKGROUND', 'FOCUS_LOST', 'HOME_OR_RECENT_APPS']))) {
        $pdo->prepare("UPDATE sessions SET status = 'locked' WHERE session_id = ?")->execute([$sessionId]);
    }
} else if ($event === 'BATAL_KELUAR') {
    $pdo->prepare("UPDATE sessions SET status = 'active' WHERE session_id = ?")->execute([$sessionId]);
}

echo json_encode([
    'success' => true,
    'newRiskScore' => $currentRisk
]);
?>
