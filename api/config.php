<?php
// config.php - Database connection settings

$host = '127.0.0.1';
$dbname = 'db_archangel';
$user = 'root';
$pass = ''; // Leave blank if default XAMPP/WAMP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// CBT Database Connection
$cbt_host = '127.0.0.1'; // Assuming same server
$cbt_dbname = 'db_cbt';
$cbt_user = 'root'; // Custom user created
$cbt_pass = ''; // NOTE: Update this with the real password in production


try {
    $pdo_cbt = new PDO("mysql:host=$cbt_host;dbname=$cbt_dbname;charset=utf8", $cbt_user, $cbt_pass);
    $pdo_cbt->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_cbt->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // We might not want to kill the whole app if CBT DB is down, 
    // but for validation, it is critical.
    echo json_encode(['error' => 'CBT Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Global Settings
$enable_strict_violations = false; // Set to false to disable Android auto-lock & risk scoring
$enable_cbt_login = true; // Set to false to disable "Mulai Ujian" button on Android
$custom_user_agent = 'Mozilla/5.0 Archangel/2.0 Archangel'; // Custom User-Agent string for Android WebView
$cbt_url = 'https://cbt.serat.us/'; // Base URL for the exam CBT

$MASTER_EXIT_SECRET = 'MAARIF-05-SECRET-KEY'; // Gantilah dengan kunci rahasia Bapak

// Advanced Security Pack Settings
$ALLOWED_APP_SIGNATURE = "94:B1:02:5E:86:74:D0:4F:DA:02:4A:F9:DA:CC:E1:4B:C8:BC:A1:07:DE:FE:C0:5E:ED:3A:4F:1A:79:93:07:D0"; // Isi dengan SHA-256 Hash APK Mas. Kosongkan untuk sementara.
$BLOCK_ADB = true; // Blokir jika USB Debugging aktif
$BLOCK_VPN = true; // Blokir jika VPN atau Proxy aktif
$BLOCK_EXTERNAL_DISPLAY = true; // Blokir jika Screen Casting aktif

/**
 * Menghasilkan Token Keluar Massal yang berganti setiap jam atau manual refresh.
 */
if (!function_exists('getMasterExitToken')) {
    function getMasterExitToken() {
        global $pdo, $MASTER_EXIT_SECRET;
        
        // Auto-create table if doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
        } catch (Exception $e) {}

        // Fetch salt from DB
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'master_token_salt'");
            $stmt->execute();
            $salt = $stmt->fetchColumn();
            
            if (!$salt) {
                $salt = bin2hex(random_bytes(16));
                $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('master_token_salt', ?)")->execute([$salt]);
            }
        } catch (Exception $e) {
            $salt = 'FALLBACK_SALT';
        }

        $timeKey = date('YmdH'); 
        $hash = hash('sha256', $MASTER_EXIT_SECRET . $timeKey . $salt);
        
        $numericHash = preg_replace('/[^0-9]/', '', $hash);
        return substr($numericHash, 0, 6);
    }
}

if (!function_exists('getMasterTokenTime')) {
    function getMasterTokenTime() {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'master_token_last_update'");
            $stmt->execute();
            return $stmt->fetchColumn() ?: date('H:i');
        } catch (Exception $e) {
            return date('H:i');
        }
    }
}
?>

