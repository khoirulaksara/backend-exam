<?php
// POST /api/check_session.php
require_once 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionId']);
    exit;
}

$sessionId = $input['sessionId'];

$stmt = $pdo->prepare("SELECT status, announcement_message FROM sessions WHERE session_id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if ($session) {
    $announcement = $session['announcement_message'];
    
    // Jika ada pesan, segera hapus dari DB setelah diambil (one-time delivery)
    if (!empty($announcement)) {
        $pdo->prepare("UPDATE sessions SET announcement_message = NULL WHERE session_id = ?")->execute([$sessionId]);
    }

    echo json_encode([
        'success' => true,
        'status' => $session['status'],
        'is_active' => ($session['status'] === 'active'),
        'announcement_message' => $announcement
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Session not found',
        'is_active' => false
    ]);
}
?>
