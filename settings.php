<?php
// settings.php - Supervisor Settings
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

require_once 'api/config.php';

// Handle Settings Update
$message = '';
if (isset($_GET['success'])) {
    $message = "Konfigurasi sistem berhasil diperbarui!";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isStrict = isset($_POST['strict_mode']) ? 'true' : 'false';
    $isCbtEnabled = isset($_POST['cbt_enabled']) ? 'true' : 'false';
    
    // Read current config
    $configFile = 'api/config.php';
    if (is_writable($configFile)) {
        $content = file_get_contents($configFile);
        
        // Replace regex for strict violations
        $content = preg_replace(
            '/\$enable_strict_violations\s*=\s*(true|false);/', 
            '$enable_strict_violations = ' . $isStrict . ';', 
            $content
        );

        // Ensure cbt_login exists before replacing, if not, append it
        if (strpos($content, '$enable_cbt_login') !== false) {
            $content = preg_replace(
                '/\$enable_cbt_login\s*=\s*(true|false);/', 
                '$enable_cbt_login = ' . $isCbtEnabled . ';', 
                $content
            );
        } else {
            $content .= "\n\$enable_cbt_login = " . $isCbtEnabled . ";\n";
        }
        
        // Handle Custom User-Agent
        $newUserAgent = isset($_POST['custom_user_agent']) ? trim($_POST['custom_user_agent']) : 'Mozilla/5.0 Archangel/2.0 Archangel';
        if (strpos($content, '$custom_user_agent') !== false) {
            $content = preg_replace(
                '/\$custom_user_agent\s*=\s*[\'"].*?[\'"];/', 
                '$custom_user_agent = \'' . addslashes($newUserAgent) . '\';', 
                $content
            );
        } else {
            $content .= "\n\$custom_user_agent = '" . addslashes($newUserAgent) . "';\n";
        }

        // Update CBT URL
        $newCbtUrl = isset($_POST['cbt_url']) ? trim($_POST['cbt_url']) : 'https://cbt.serat.us/';
        if (strpos($content, '$cbt_url') !== false) {
            $content = preg_replace('/\$cbt_url\s*=\s*[\'"].*?[\'"];/', '$cbt_url = \'' . addslashes($newCbtUrl) . '\';', $content);
        } else {
            $content .= "\n\$cbt_url = '" . addslashes($newCbtUrl) . "';\n";
        }

        // Update Allowed Signature
        $newSignature = isset($_POST['allowed_signature']) ? trim($_POST['allowed_signature']) : '';
        if (strpos($content, '$ALLOWED_APP_SIGNATURE') !== false) {
            $content = preg_replace('/\$ALLOWED_APP_SIGNATURE\s*=\s*[\'"].*?[\'"];/', '$ALLOWED_APP_SIGNATURE = \'' . addslashes($newSignature) . '\';', $content);
        } else {
            $content .= "\n\$ALLOWED_APP_SIGNATURE = '" . addslashes($newSignature) . "';\n";
        }
        
        // Anti-ADB/VPN Settings Update (Optional additions, but checking for safety)
        $isAdb = isset($_POST['block_adb']) ? 'true' : 'false';
        $isVpn = isset($_POST['block_vpn']) ? 'true' : 'false';
        $isExt = isset($_POST['block_external']) ? 'true' : 'false';
        
        $content = preg_replace('/\$BLOCK_ADB\s*=\s*(true|false);/', '$BLOCK_ADB = ' . $isAdb . ';', $content);
        $content = preg_replace('/\$BLOCK_VPN\s*=\s*(true|false);/', '$BLOCK_VPN = ' . $isVpn . ';', $content);
        $content = preg_replace('/\$BLOCK_EXTERNAL_DISPLAY\s*=\s*(true|false);/', '$BLOCK_EXTERNAL_DISPLAY = ' . $isExt . ';', $content);

        file_put_contents($configFile, $content);
        header("Location: settings.php?success=1");
        exit;
    } else {
        $message = "Error: File config.php tidak dapat ditulis.";
    }

    // Handle Prefixes Update
    if (isset($_POST['prefixes']) && is_array($_POST['prefixes'])) {
        $newPrefixes = [];
        foreach ($_POST['prefixes'] as $p) {
            if (!empty($p['label']) && !empty($p['prefix'])) {
                $newPrefixes[] = [
                    'label' => trim($p['label']),
                    'prefix' => trim($p['prefix'])
                ];
            }
        }
        if (!empty($newPrefixes)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('nis_prefixes', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $json = json_encode($newPrefixes);
                $stmt->execute([$json, $json]);
            } catch (Exception $e) {}
        }
    }
}

// Fetch current prefixes
$prefixes = [['label' => 'Kelas X', 'prefix' => '12-251-001-'], ['label' => 'Kelas XI', 'prefix' => '12-250-001-'], ['label' => 'Kelas XII', 'prefix' => '12-249-001-']];
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'nis_prefixes'");
    $stmt->execute();
    $result = $stmt->fetch();
    if ($result && !empty($result['setting_value'])) $prefixes = json_decode($result['setting_value'], true);
} catch (Exception $e) {}

$page_title = "System Settings";
include 'header.php';
?>

<div class="mb-10">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Pengaturan Sistem</h1>
    <p class="text-slate-500 font-medium">Konfigurasi aturan ujian dan keamanan aplikasi.</p>
</div>

<?php if ($message): ?>
    <div class="bg-blue-600 text-white p-5 rounded-2xl mb-8 shadow-lg shadow-blue-100 flex items-center gap-4 animate-bounce">
        <i class="fas fa-check-circle text-2xl"></i>
        <span class="font-bold"><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
    <form method="POST" action="" class="space-y-8">
        <!-- Security Rules Card -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center gap-3">
                <i class="fas fa-shield-virus text-primary"></i>
                <h3 class="font-bold text-slate-800">Aturan Keamanan Ujian</h3>
            </div>
            <div class="p-8 space-y-8">
                <!-- Toggles -->
                <div class="flex items-start gap-4">
                    <div class="mt-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="strict_mode" value="1" class="sr-only peer" <?= $enable_strict_violations ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Mode Pelanggaran Ketat (Strict)</span>
                        <span class="block text-[11px] text-slate-400 leading-relaxed mt-1">Siswa akan otomatis terkunci jika mencoba keluar aplikasi berkali-kali.</span>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="mt-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="cbt_enabled" value="1" class="sr-only peer" <?= (isset($enable_cbt_login) && $enable_cbt_login) ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Akses Login Ujian (CBT Access)</span>
                        <span class="block text-[11px] text-slate-400 leading-relaxed mt-1">Aktifkan tombol "Mulai Ujian" di HP siswa.</span>
                    </div>
                </div>

                <hr class="border-slate-50">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col items-center text-center group cursor-pointer hover:border-primary/30 transition-all">
                        <input type="checkbox" name="block_adb" value="1" class="mb-3 w-4 h-4 text-primary" <?= ($BLOCK_ADB ?? true) ? 'checked' : '' ?>>
                        <span class="text-[10px] font-black text-slate-800 uppercase">Block ADB</span>
                    </label>
                    <label class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col items-center text-center group cursor-pointer hover:border-primary/30 transition-all">
                        <input type="checkbox" name="block_vpn" value="1" class="mb-3 w-4 h-4 text-primary" <?= ($BLOCK_VPN ?? true) ? 'checked' : '' ?>>
                        <span class="text-[10px] font-black text-slate-800 uppercase">Block VPN</span>
                    </label>
                    <label class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col items-center text-center group cursor-pointer hover:border-primary/30 transition-all">
                        <input type="checkbox" name="block_external" value="1" class="mb-3 w-4 h-4 text-primary" <?= ($BLOCK_EXTERNAL_DISPLAY ?? true) ? 'checked' : '' ?>>
                        <span class="text-[10px] font-black text-slate-800 uppercase">Block Cast</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- User Agent Card -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center gap-3">
                <i class="fas fa-fingerprint text-primary"></i>
                <h3 class="font-bold text-slate-800">Identitas App & Endpoint</h3>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">User-Agent Pola (Security)</label>
                    <input type="text" name="custom_user_agent" value="<?= isset($custom_user_agent) ? htmlspecialchars($custom_user_agent) : 'Mozilla/5.0 Archangel/2.0 Archangel' ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm font-mono text-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Endpoint URL CBT (Dinamis)</label>
                    <input type="text" name="cbt_url" value="<?= isset($cbt_url) ? htmlspecialchars($cbt_url) : '' ?>" placeholder="https://cbt.sekolah.sch.id/" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Allowed App Signature (SHA-256)</label>
                    <input type="text" name="allowed_signature" value="<?= isset($ALLOWED_APP_SIGNATURE) ? htmlspecialchars($ALLOWED_APP_SIGNATURE) : '' ?>" placeholder="Masukkan Hash SHA-256 APK..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm font-mono text-red-600 focus:ring-2 focus:ring-red-500/10 focus:outline-none transition-all">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white py-5 rounded-[2rem] font-black text-sm transition-all shadow-xl active:scale-95">
            SIMPAN PERUBAHAN
        </button>
    </form>

    <!-- NIS Prefixes Card -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden lg:sticky lg:top-8">
        <div class="p-8 border-b border-slate-50 bg-slate-50/50">
            <h3 class="font-black text-slate-900 flex items-center gap-3">
                <i class="fas fa-id-card text-primary"></i>
                NIS Prefixes
            </h3>
        </div>
        <div class="p-8">
            <form method="POST" action="" class="space-y-6">
                <?php 
                $slots = array_pad($prefixes, 3, ['label' => '', 'prefix' => '']);
                for ($i = 0; $i < 3; $i++): 
                ?>
                <div class="p-6 rounded-3xl bg-slate-50/50 border border-slate-100">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Label Kelas</label>
                            <input type="text" name="prefixes[<?= $i ?>][label]" value="<?= htmlspecialchars($slots[$i]['label']) ?>" class="w-full bg-white border border-slate-100 rounded-xl py-3 px-4 text-sm font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Awalan NIS</label>
                            <input type="text" name="prefixes[<?= $i ?>][prefix]" value="<?= htmlspecialchars($slots[$i]['prefix']) ?>" class="w-full bg-white border border-slate-100 rounded-xl py-3 px-4 text-sm font-mono text-blue-600">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
