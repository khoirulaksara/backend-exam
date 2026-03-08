<?php
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

try {
    // Fetch stats
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'locked' THEN 1 ELSE 0 END) as locked_count,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count
        FROM sessions");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch live sessions
    $stmt = $pdo->query("SELECT s.*, 
        (SELECT event_type FROM violations v WHERE v.session_id = s.session_id ORDER BY id DESC LIMIT 1) as last_event,
        (SELECT MAX(duration_seconds) FROM violations v WHERE v.session_id = s.session_id AND v.duration_seconds >= 10 AND v.timestamp > DATE_SUB(NOW(), INTERVAL 10 MINUTE)) as max_recent_absence
        FROM sessions s ORDER BY s.risk_score DESC, s.created_at DESC");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enrich sessions with student names from CBT DB
    $longAbsenceCount = 0;
    foreach ($sessions as &$session) {
        $stmtCbt = $pdo_cbt->prepare("SELECT nama FROM siswa WHERE no_peserta = ?");
        $stmtCbt->execute([$session['session_id']]);
        $studentData = $stmtCbt->fetch(PDO::FETCH_ASSOC);
        $session['nama'] = $studentData ? $studentData['nama'] : 'Unknown';
        $session['has_long_absence'] = !empty($session['max_recent_absence']);
        if ($session['has_long_absence']) $longAbsenceCount++;
    }

    echo json_encode([
        'stats' => array_merge($stats, ['long_absence_count' => $longAbsenceCount]),
        'sessions' => $sessions
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
