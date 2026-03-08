<?php
// refresh_master_token.php
require_once 'config.php';

session_start();
// Basic security check - in production use real auth
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Auto-create table if doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");

    $newSalt = bin2hex(random_bytes(16));
    $timestamp = date('H:i');
    
    // Update salt
    $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('master_token_salt', ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$newSalt, $newSalt]);

    // Update timestamp
    $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('master_token_last_update', ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$timestamp, $timestamp]);
    
    echo json_encode(['success' => true, 'new_token' => getMasterExitToken(), 'time' => $timestamp]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
