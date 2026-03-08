<?php
// POST /api/send_announcement.php
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionId or message']);
    exit;
}

$sessionId = $input['sessionId'];
$message = trim($input['message']);

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE sessions SET announcement_message = ? WHERE session_id = ?");
    $stmt->execute([$message, $sessionId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Pengumuman telah dikirim ke perangkat siswa']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Sesi tidak temukan atau tidak ada perubahan']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
