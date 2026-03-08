<?php
// POST /api/start_session.php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['nis']) || !isset($input['device_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing nis or device_id']);
    exit;
}

$sessionId = trim($input['nis']);
$deviceId = trim($input['device_id']);
$deviceInfo = isset($input['device_info']) ? json_encode($input['device_info']) : null;
$signatureHash = $input['signature_hash'] ?? '';
$isAdb = $input['is_adb'] ?? false;
$isVpn = $input['is_vpn'] ?? false;
$isExternalDisplay = $input['is_external_display'] ?? false;

$studentId = "NIS-" . $sessionId; 
$examId = "EXAM-001"; 

// 0. Auto-Update Database Structure (Hanya sekali jalan)
try {
    $pdo->exec("ALTER TABLE sessions ADD COLUMN IF NOT EXISTS signature_hash VARCHAR(255), ADD COLUMN IF NOT EXISTS is_adb BOOLEAN DEFAULT 0, ADD COLUMN IF NOT EXISTS is_vpn BOOLEAN DEFAULT 0, ADD COLUMN IF NOT EXISTS is_external_display BOOLEAN DEFAULT 0, ADD COLUMN IF NOT EXISTS announcement_message TEXT");
} catch (PDOException $e) {}

try {
    // 1. Fitur #5: Pengecekan Integritas APK (Signature Check)
    if (!empty($ALLOWED_APP_SIGNATURE) && $signatureHash !== $ALLOWED_APP_SIGNATURE) {
        echo json_encode([
            'success' => false,
            'message' => 'Aplikasi Tidak Resmi! Gunakan APK Archangel Original (Fingerprint Mismatch).',
            'status' => 'invalid_signature'
        ]);
        exit;
    }

    // 2. Fitur #1: Blokir ADB / Debugging
    if ($BLOCK_ADB && $isAdb) {
        echo json_encode([
            'success' => false,
            'message' => 'USB Debugging Terdeteksi! Matikan USB Debugging di Opsi Pengembang untuk melanjutkan.',
            'status' => 'adb_active'
        ]);
        exit;
    }

    // 3. Fitur #2: Blokir VPN / Proxy
    if ($BLOCK_VPN && $isVpn) {
        echo json_encode([
            'success' => false,
            'message' => 'VPN atau Proxy Terdeteksi! Matikan VPN Anda untuk login ke ujian.',
            'status' => 'vpn_active'
        ]);
        exit;
    }

    // 4. Fitur #3: Blokir Layar Eksternal (Screen Mirroring)
    if ($BLOCK_EXTERNAL_DISPLAY && $isExternalDisplay) {
        echo json_encode([
            'success' => false,
            'message' => 'Layar Eksternal Terdeteksi! Lepaskan kabel HDMI atau hentikan Screen Mirroring.',
            'status' => 'external_display_active'
        ]);
        exit;
    }

    // 5. Validasi NIS ke Database CBT
    $stmtCbt = $pdo_cbt->prepare("SELECT nama, username FROM siswa WHERE no_peserta = ?");
    $stmtCbt->execute([$sessionId]);
    $studentData = $stmtCbt->fetch();

    if (!$studentData) {
        echo json_encode(['success' => false, 'message' => 'Nomor Peserta tidak ditemukan di sistem ujian.', 'status' => 'invalid_nis']);
        exit;
    }
    
    $studentName = $studentData['nama'];
    $cbtUsername = $studentData['username'] ?? '';

    // 6. Cek apakah Perangkat ini sudah dipakai oleh NIS lain
    $stmtDevice = $pdo->prepare("SELECT session_id FROM sessions WHERE device_id = ? AND session_id != ?");
    $stmtDevice->execute([$deviceId, $sessionId]);
    $deviceUsedByOther = $stmtDevice->fetch();

    if ($deviceUsedByOther) {
        echo json_encode(['success' => false, 'message' => 'Perangkat ini sudah terdaftar untuk peserta lain (' . $deviceUsedByOther['session_id'] . '). Hubungi pengawas.', 'status' => 'device_in_use']);
        exit;
    }

    // 7. Cek apakah NIS ini sudah login di perangkat lain
    $stmt = $pdo->prepare("SELECT status, device_id FROM sessions WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['device_id'] !== $deviceId) {
            echo json_encode(['success' => false, 'message' => 'NIS Anda sudah login di perangkat lain! Hubungi pengawas.', 'status' => 'nis_in_use']);
            exit;
        }

        // Update data terbaru
        $update = $pdo->prepare("UPDATE sessions SET signature_hash = ?, is_adb = ?, is_vpn = ?, is_external_display = ?, device_info = ? WHERE session_id = ?");
        $update->execute([$signatureHash, $isAdb, $isVpn, $isExternalDisplay, $deviceInfo, $sessionId]);

        echo json_encode(['success' => true, 'message' => 'Session resumed', 'status' => $existing['status'], 'nama' => $studentName, 'username' => $cbtUsername]);
    } else {
        // Buat sesi baru
        $insert = $pdo->prepare("INSERT INTO sessions (session_id, student_id, device_id, exam_id, status, risk_score, device_info, signature_hash, is_adb, is_vpn, is_external_display) VALUES (?, ?, ?, ?, 'active', 0, ?, ?, ?, ?, ?)");
        $insert->execute([$sessionId, $studentId, $deviceId, $examId, $deviceInfo, $signatureHash, $isAdb, $isVpn, $isExternalDisplay]);
        
        echo json_encode(['success' => true, 'message' => 'Session created successfully', 'status' => 'active', 'nama' => $studentName, 'username' => $cbtUsername]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
?>
