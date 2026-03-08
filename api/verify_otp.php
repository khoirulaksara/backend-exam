<?php
// POST /otp/verify
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId']) || !isset($input['otp'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionId or otp']);
    exit;
}

$sessionId = $input['sessionId'];
$otp = $input['otp'];
$expectedType = isset($input['otpType']) ? $input['otpType'] : 'unlock';

// 1. CEK MASTER EXIT TOKEN (Global 1 Jam)
if ($expectedType === 'exit') {
    $masterToken = getMasterExitToken();
    if ($otp === $masterToken) {
        // VALID: Hapus sesi karena ini izin keluar resmi
        $pdo->prepare("DELETE FROM sessions WHERE session_id = ?")->execute([$sessionId]);
        echo json_encode([
            'status' => 'valid', 
            'riskScore' => 0, 
            'message' => 'Sesi ujian selesai (Master Token)'
        ]);
        exit;
    }
}

// 2. CEK DATABASE OTP (Untuk Unlock/Gembok Individu)
$stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE session_id = ? AND otp_type = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$sessionId, $expectedType]);
$otpRecord = $stmt->fetch();

if (!$otpRecord) {
    // Tweak pesan error agar lebih jelas
    $stmtAny = $pdo->prepare("SELECT * FROM otp_codes WHERE session_id = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmtAny->execute([$sessionId]);
    if ($stmtAny->fetch()) {
        echo json_encode(['status' => 'invalid', 'message' => "OTP yang Anda masukkan salah tipe. Ini OTP untuk mode lain."]);
    } else {
        echo json_encode(['status' => 'invalid', 'message' => 'OTP Salah atau Kadaluarsa']);
    }
    exit;
}

// Check expiration
if (strtotime($otpRecord['expires_at']) < time()) {
    echo json_encode(['status' => 'invalid', 'message' => 'OTP expired']);
    exit;
}

// Check attempt limits
if ($otpRecord['attempt_count'] >= 3) {
    // If 3 invalid -> auto submit
    $pdo->prepare("UPDATE sessions SET status = 'submitted' WHERE session_id = ?")->execute([$sessionId]);
    echo json_encode(['status' => 'auto_submit', 'message' => 'Max attempts reached. Auto submitting.']);
    exit;
}

if ($otpRecord['otp_code'] === $otp) {
    // Valid
    $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?")->execute([$otpRecord['id']]);
    
    // Mengecek alasan terkunci terakhir kali
    $stmtLastViol = $pdo->prepare("SELECT event_type FROM violations WHERE session_id = ? ORDER BY id DESC LIMIT 1");
    $stmtLastViol->execute([$sessionId]);
    $lastViolation = $stmtLastViol->fetch();

    if ($lastViolation && $lastViolation['event_type'] === 'PERMINTAAN_KELUAR') {
        // Jika OTP ini khusus untuk keluar aplikasi, HAPUS SESI SECARA PERMANEN DARI DATABASE
        $pdo->prepare("DELETE FROM sessions WHERE session_id = ?")->execute([$sessionId]);
        echo json_encode(['status' => 'valid', 'riskScore' => 0, 'message' => 'Sesi ujian telah dihapus didatabase']);
        exit;
    } else {
        // Jika OTP ini untuk buka kunci biasa, kembalikan ke aktif
        $pdo->prepare("UPDATE sessions SET status = 'active' WHERE session_id = ?")->execute([$sessionId]);
        // Catat sebagai log "UNLOCK_SUCCESS" agar report_violation bisa mendeteksi cooldown (mencegah race condition)
        $pdo->prepare("INSERT INTO violations (session_id, event_type, risk_value) VALUES (?, 'UNLOCK_SUCCESS', 0)")->execute([$sessionId]);
    }
    
    // Get updated risk score
    $stmt = $pdo->prepare("SELECT risk_score FROM sessions WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    echo json_encode(['status' => 'valid', 'riskScore' => $session['risk_score']]);
} else {
    // Invalid
    $pdo->prepare("UPDATE otp_codes SET attempt_count = attempt_count + 1 WHERE id = ?")->execute([$otpRecord['id']]);
    // Increase risk for invalid OTP attempt (+15)
    $pdo->prepare("UPDATE sessions SET risk_score = risk_score + 15 WHERE session_id = ?")->execute([$sessionId]);
    
    // Check if max attempts reached now
    if ($otpRecord['attempt_count'] + 1 >= 3) {
        $pdo->prepare("UPDATE sessions SET status = 'submitted' WHERE session_id = ?")->execute([$sessionId]);
        echo json_encode(['status' => 'auto_submit', 'message' => 'Max attempts reached on this try. Auto submitting.']);
    } else {
        echo json_encode(['status' => 'invalid', 'message' => 'Incorrect OTP']);
    }
}
?>
