<?php
// POST /session/prefixes
require_once 'config.php';
header('Content-Type: application/json');

// We use the app_settings table to store the JSON string 
// of the current class prefixes. 
// If it doesn't exist, we return the default hardcoded ones.

try {
    // Ensure table exists (safeguard)
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");

    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'nis_prefixes'");
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && !empty($result['setting_value'])) {
        $prefixes = json_decode($result['setting_value'], true);
        echo json_encode([
            'success' => true,
            'prefixes' => $prefixes
        ]);
    } else {
        // Return defaults and optionally auto-insert them to DB for future editing
        $defaultPrefixes = [
            ['label' => 'Kelas X', 'prefix' => '12-251-001-'],
            ['label' => 'Kelas XI', 'prefix' => '12-250-001-'],
            ['label' => 'Kelas XII', 'prefix' => '12-249-001-']
        ];
        
        $insert = $pdo->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES ('nis_prefixes', ?)");
        $insert->execute([json_encode($defaultPrefixes)]);

        echo json_encode([
            'success' => true,
            'prefixes' => $defaultPrefixes
        ]);
    }
} catch (Exception $e) {
    // Graceful degradation
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch prefixes: ' . $e->getMessage()
    ]);
}
?>
