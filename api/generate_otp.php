<?php
// POST /otp/generate
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId']) || !isset($input['supervisorId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionId or supervisorId']);
    exit;
}

$sessionId = $input['sessionId'];
$supervisorId = $input['supervisorId'];
$otpType = isset($input['otpType']) ? $input['otpType'] : 'unlock';

if ($otpType === 'exit') {
    // Jika untuk izin keluar, hanya lock saja (berada di Lock Overlay) agar tidak ketambah hukuman
    $stmt = $pdo->prepare("UPDATE sessions SET status = 'locked' WHERE session_id = ?");
    $stmt->execute([$sessionId]);
} else {
    // 1. Mark session as LOCKED and optionally add +20 risk based on strict mode
    if ($enable_strict_violations) {
        $stmt = $pdo->prepare("UPDATE sessions SET status = 'locked', risk_score = risk_score + 20 WHERE session_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE sessions SET status = 'locked' WHERE session_id = ?");
    }
    $stmt->execute([$sessionId]);
}

// 2. Generate 6 digit OTP
if ($otpType === 'exit') {
    // Gunakan Master Token untuk tipe 'exit'
    $otp = getMasterExitToken();
    $expiresInSecs = 3600; // Informatif saja (1 jam)
} else {
    // Gunakan Random OTP untuk tipe 'unlock'
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresInSecs = 60;
    
    // Save to DB only for 'unlock' type
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresInSecs);
    $stmt = $pdo->prepare("INSERT INTO otp_codes (session_id, otp_code, otp_type, expires_at, generated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$sessionId, $otp, $otpType, $expiresAt, $supervisorId]);
}

echo json_encode([
    'otp' => $otp,
    'expiresIn' => $expiresInSecs
]);
?>
