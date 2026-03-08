<?php
// index.php - Supervisor Dashboard
session_start();

// Hardcoded Password Setting
$SUPERVISOR_PASSWORD = 'Alhamdulillah';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle Login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $SUPERVISOR_PASSWORD) {
        $_SESSION['loggedin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Password salah!';
    }
}

// Cek status login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Tampilkan Form Login (Gaya Baru)
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="theme-color" content="#1e293b">
        <title>Login Hub - Archangel</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Inter', sans-serif; }</style>
    </head>
    <body class="bg-[#0f172a] flex h-screen items-center justify-center p-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl -mr-48 -mt-48 transition-all"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl -ml-48 -mb-48 transition-all"></div>
        
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 md:p-12 rounded-[2.5rem] shadow-2xl w-full max-w-md relative z-10 transition-all">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-blue-600/20 rotate-3">
                    <i class="fas fa-shield-halved text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-black text-white tracking-tight mb-2">ARCHANGEL HUB</h2>
                <p class="text-slate-400 text-sm font-medium">Sistem Monitoring Ujian & Keamanan</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-2xl mb-6 text-xs font-bold text-center flex items-center justify-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1" for="password">Supervisor Access Code</label>
                    <input class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 text-white leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-mono placeholder:text-slate-600" id="password" type="password" name="password" placeholder="••••••••••••" required autofocus>
                </div>
                <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-6 rounded-2xl transition-all shadow-xl shadow-blue-600/20 active:scale-95" type="submit">
                    MASUK SISTEM
                </button>
            </form>
            
            <p class="mt-10 text-center text-[10px] text-slate-500 font-bold uppercase tracking-widest">v2.0 Archangel Project</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require_once 'api/config.php';

// Fetch stats
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'locked' THEN 1 ELSE 0 END) as locked_count,
    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count
    FROM sessions");
$stats = $stmt->fetch();

// Live sessions
$stmt = $pdo->query("SELECT s.*, 
    (SELECT event_type FROM violations v WHERE v.session_id = s.session_id ORDER BY id DESC LIMIT 1) as last_event
    FROM sessions s ORDER BY s.risk_score DESC, s.created_at DESC");
$sessions = $stmt->fetchAll();

// Long exit count
$stmtLong = $pdo->query("SELECT COUNT(DISTINCT session_id) as long_count FROM violations WHERE duration_seconds >= 10 AND timestamp > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
$longAbsenceCount = (int)($stmtLong->fetch()['long_count'] ?? 0);

// Enrich names
foreach ($sessions as &$session) {
    try {
        $stmtCbt = $pdo_cbt->prepare("SELECT nama FROM siswa WHERE no_peserta = ?");
        $stmtCbt->execute([$session['session_id']]);
        $studentData = $stmtCbt->fetch(PDO::FETCH_ASSOC);
        $session['nama'] = $studentData ? $studentData['nama'] : 'Tidak Terdaftar';
    } catch(Exception $e) { $session['nama'] = 'Unknown'; }
}

$page_title = "Live Sessions";
include 'header.php';
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Live Active Exams</h1>
        <p class="text-slate-500 font-medium">Monitoring aktivitas siswa secara real-time.</p>
    </div>
    <div class="flex items-center gap-3 bg-white px-5 py-2.5 rounded-2xl shadow-sm border border-slate-100">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
        <span class="text-xs font-black text-slate-800 uppercase tracking-widest">Auto-Sync Active</span>
    </div>
</div>

<!-- Stats Dashboard -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total Peserta</p>
        <div class="flex items-end justify-between">
            <p class="text-4xl font-black text-slate-900 leading-none" id="statTotal"><?= count($sessions) ?></p>
            <i class="fas fa-users text-2xl text-slate-100"></i>
        </div>
    </div>
    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Siswa Aktif</p>
        <div class="flex items-end justify-between">
            <p class="text-4xl font-black text-green-500 leading-none" id="statActive"><?= count(array_filter($sessions, fn($s) => $s['status'] == 'active')) ?></p>
            <i class="fas fa-check-circle text-2xl text-green-50"></i>
        </div>
    </div>
    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden">
        <div id="statLockedBgAlert" class="absolute inset-0 bg-red-500 opacity-0 transition-opacity"></div>
        <div class="relative z-10">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Device Terkunci</p>
            <div class="flex items-end justify-between">
                <p class="text-4xl font-black text-red-500 leading-none" id="statLocked"><?= count(array_filter($sessions, fn($s) => $s['status'] == 'locked')) ?></p>
                <i class="fas fa-lock text-2xl text-red-100"></i>
            </div>
        </div>
    </div>
    <div class="bg-primary p-8 rounded-[2rem] shadow-xl shadow-primary/20 text-white leading-tight flex flex-col justify-between">
        <div>
            <p class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em] mb-3">Master Exit Token</p>
            <div class="flex items-center justify-between gap-2">
                <p id="masterTokenDisplay" class="text-3xl font-black tracking-widest"><?= getMasterExitToken() ?></p>
                <button id="refreshTokenBtn" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all">
                    <i class="fas fa-sync-alt text-xs"></i>
                </button>
            </div>
        </div>
        <p class="text-[10px] font-bold text-white/60 mt-4 uppercase">Expire: <?= date('H:59') ?></p>
    </div>
</div>

<!-- Table Area -->
<div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-8 overflow-x-auto w-full">
        <table id="sessionTable" class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-slate-100">
                    <th class="pb-6 pt-2 px-4">NIS / Identitas</th>
                    <th class="pb-6 pt-2 px-4">Nama Siswa</th>
                    <th class="pb-6 pt-2 px-4">Hardware</th>
                    <th class="pb-6 pt-2 px-4 text-center">Keamanan</th>
                    <th class="pb-6 pt-2 px-4 text-center">Score</th>
                    <th class="pb-6 pt-2 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="sessionTableBody">
                <?php foreach ($sessions as $session): ?>
                <tr class="border-b border-slate-50 group hover:bg-slate-50/50 transition-all">
                    <td class="p-5">
                        <span class="text-xs font-black text-slate-800 font-mono"><?= htmlspecialchars($session['session_id']) ?></span>
                    </td>
                    <td class="p-5">
                        <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($session['nama']) ?></span>
                    </td>
                    <td class="p-5">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-medium text-slate-400"><?= substr($session['device_id'], 0, 12) ?>...</span>
                            <?php if (!empty($session['device_info'])): ?>
                                <?php $di = json_decode($session['device_info'], true); ?>
                                <button class="btn-device-info text-primary hover:scale-110 transition-all" 
                                    data-name="<?= htmlspecialchars($di['name'] ?? 'Unknown') ?>"
                                    data-manufacturer="<?= htmlspecialchars($di['manufacturer'] ?? 'Unknown') ?>"
                                    data-model="<?= htmlspecialchars($di['model'] ?? 'Unknown') ?>"
                                    data-android="<?= htmlspecialchars($di['android_version'] ?? 'Unknown') ?>"
                                    data-sdk="<?= htmlspecialchars($di['sdk_int'] ?? 'Unknown') ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="p-5 text-center px-4">
                        <?php if ($session['status'] === 'active' && $session['last_event'] !== 'PERMINTAAN_BUKA_KUNCI'): ?>
                            <span class="px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[10px] font-black uppercase tracking-tight">Active</span>
                        <?php elseif ($session['status'] === 'locked' || $session['last_event'] === 'PERMINTAAN_BUKA_KUNCI'): ?>
                            <span class="px-3 py-1 bg-red-600 text-white rounded-full text-[10px] font-black uppercase tracking-tight shadow-lg shadow-red-200">Locked / Alert</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-slate-200 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-tight">Submitted</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-5 text-center">
                        <span class="text-lg font-black <?= $session['risk_score'] > 50 ? 'text-red-500' : 'text-slate-900' ?>">
                            <?= $session['risk_score'] ?>
                        </span>
                    </td>
                    <td class="p-5 text-right flex items-center justify-end gap-2">
                        <button class="btn-send-msg bg-primary hover:bg-primary-dark text-white w-10 h-10 rounded-xl transition-all flex items-center justify-center shadow-lg shadow-primary/20 active:scale-95" 
                            data-session-id="<?= $session['session_id'] ?>" 
                            data-nama="<?= htmlspecialchars($session['nama']) ?>"
                            title="Kirim Pesan ke Siswa">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <?php if ($session['status'] === 'locked' || $session['last_event'] === 'PERMINTAAN_BUKA_KUNCI'): ?>
                            <button class="btn-generate-otp bg-slate-900 hover:bg-black text-white px-5 py-2.5 rounded-2xl text-[11px] font-black tracking-tight transition-all active:scale-95 flex items-center gap-2" 
                                data-session-id="<?= $session['session_id'] ?>" 
                                data-otp-type="unlock">
                                <i class="fas fa-key"></i> BUKA KUNCI
                            </button>
                        <?php else: ?>
                            <span class="text-[10px] font-black text-slate-300 uppercase italic">On Exam</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-dark/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-sm w-full text-center border border-white/20 animate-in fade-in zoom-in duration-300">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shield-alt text-2xl"></i>
        </div>
        <h2 class="text-xl font-black text-slate-900 mb-2">AUTH CODE</h2>
        <p class="text-slate-400 text-sm font-medium mb-8">Berikan kode ini ke siswa: <br><strong class="text-slate-900" id="modalSessionId"></strong></p>
        <div class="text-5xl font-mono tracking-[0.2em] font-black text-primary bg-slate-50 p-6 rounded-3xl mb-10 shadow-inner" id="otpDisplay">...</div>
        <button onclick="closeModal()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 py-4 px-6 rounded-2xl font-black text-sm transition-all active:scale-95 uppercase tracking-widest">Tutup</button>
    </div>
</div>

<!-- Announcement Modal -->
<div id="msgModal" class="fixed inset-0 bg-dark/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full border border-white/20 animate-in fade-in zoom-in duration-300">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Kirim Pengumuman</h2>
            <button onclick="closeMsgModal()" class="w-10 h-10 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 mb-8">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Penerima</p>
            <p id="msgSiswaName" class="font-bold text-slate-800">-</p>
            <input type="hidden" id="msgSessionId">
        </div>
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3" for="msgContent">Pesan Pengawas</label>
            <textarea id="msgContent" class="w-full bg-white border border-slate-100 rounded-3xl py-5 px-6 text-slate-700 min-h-[120px] focus:ring-4 focus:ring-primary/10 focus:outline-none transition-all placeholder:text-slate-300" placeholder="Contoh: Fokus ke layar ujian!, Matikan VPN sekarang."></textarea>
        </div>
        <div class="mt-8">
            <button id="sendAnnouncementBtn" class="w-full bg-primary hover:bg-primary-dark text-white py-5 rounded-[1.5rem] font-black text-sm transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                KIRIM PESAN <i class="fas fa-paper-plane text-xs"></i>
            </button>
        </div>
    </div>
</div>

<!-- Device Info Modal -->
<div id="deviceInfoModal" class="fixed inset-0 bg-dark/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full border border-white/20">
        <div class="flex justify-between items-center mb-10">
            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Perangkat Siswa</h2>
            <button onclick="closeDeviceInfoModal()" class="w-10 h-10 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="space-y-6">
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Model Perangkat</p>
                <p id="diModalName" class="text-xl font-black text-primary">-</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Manufacturer</p>
                    <p id="diModalManufacturer" class="font-bold text-slate-800">-</p>
                </div>
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Model ID</p>
                    <p id="diModalModel" class="font-bold text-slate-800">-</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Android</p>
                    <p id="diModalAndroid" class="font-bold text-slate-800">-</p>
                </div>
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">SDK</p>
                    <p id="diModalSdk" class="font-bold text-slate-800">-</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const dtRender = $('#sessionTable').DataTable({
            "pageLength": 25,
            "stateSave": true,
            "order": [[4, "desc"]],
            "dom": '<"flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4"f>rt<"flex flex-col md:flex-row md:items-center justify-between gap-4 mt-8"ip>',
            "language": {
                "search": "",
                "searchPlaceholder": "Cari NIS atau Nama Siswa...",
                "emptyTable": "Belum ada sesi ujian aktif.",
                "info": "Menampilkan <span class='font-bold text-slate-900'>_START_ - _END_</span> dari _TOTAL_ siswa"
            }
        });

        // AJAX Polling
        setInterval(async () => {
            try {
                const res = await fetch('api/get_sessions.php');
                if (!res.ok) return;
                const data = await res.json();
                
                if(data.stats) {
                    $('#statTotal').text(data.stats.total || 0);
                    $('#statActive').text(data.stats.active_count || 0);
                    
                    const oldLocked = parseInt($('#statLocked').text());
                    const newLocked = parseInt(data.stats.locked_count || 0);
                    $('#statLocked').text(newLocked);

                    if (newLocked > oldLocked) {
                        $('#statLockedBgAlert').addClass('opacity-100');
                        setTimeout(() => $('#statLockedBgAlert').removeClass('opacity-100'), 1000);
                        try { new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU').play(); } catch(e){}
                    }
                }

                if(data.sessions) {
                    dtRender.clear();
                    data.sessions.forEach(s => {
                        let statusHtml = '';
                        if (s.status === 'active' && s.last_event !== 'PERMINTAAN_BUKA_KUNCI') {
                            statusHtml = '<span class="px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[10px] font-black uppercase tracking-tight">Active</span>';
                        } else if (s.status === 'locked' || s.last_event === 'PERMINTAAN_BUKA_KUNCI') {
                            statusHtml = '<span class="px-3 py-1 bg-red-600 text-white rounded-full text-[10px] font-black uppercase tracking-tight">Locked / Alert</span>';
                        } else {
                            statusHtml = '<span class="px-3 py-1 bg-slate-200 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-tight">Submitted</span>';
                        }

                        let actionHtml = (s.status === 'locked' || s.last_event === 'PERMINTAAN_BUKA_KUNCI') ? 
                            '<button class="btn-generate-otp bg-slate-900 hover:bg-black text-white px-5 py-2.5 rounded-2xl text-[11px] font-black transition-all active:scale-95 flex items-center gap-2 ml-auto" data-session-id="'+s.session_id+'" data-otp-type="unlock"><i class="fas fa-key"></i> BUKA KUNCI</button>' : 
                            '<span class="text-[10px] font-black text-slate-300 uppercase italic">On Exam</span>';

                        let diBtn = '';
                        if (s.device_info) {
                            try {
                                let di = JSON.parse(s.device_info);
                                diBtn = '<button class="btn-device-info text-primary hover:scale-110 transition-all ml-2" data-name="'+(di.name||'Unknown')+'" data-manufacturer="'+(di.manufacturer||'Unknown')+'" data-model="'+(di.model||'Unknown')+'" data-android="'+(di.android_version||'Unknown')+'" data-sdk="'+(di.sdk_int||'Unknown')+'"><i class="fas fa-info-circle"></i></button>';
                            } catch(e){}
                        }

                        dtRender.row.add([
                            '<span class="text-xs font-black text-slate-800 font-mono">'+s.session_id+'</span>',
                            '<span class="text-sm font-bold text-slate-700">'+s.nama+'</span>',
                            '<div class="flex items-center gap-2"><span class="text-[10px] font-medium text-slate-400">'+s.device_id.substring(0,12)+'...</span>'+diBtn+'</div>',
                            statusHtml,
                            '<span class="text-lg font-black '+(s.risk_score > 50 ? 'text-red-500' : 'text-slate-900')+'">'+s.risk_score+'</span>',
                            '<div class="flex items-center justify-end gap-2">' +
                             '<button class="btn-send-msg bg-primary hover:bg-primary-dark text-white w-10 h-10 rounded-xl transition-all flex items-center justify-center shadow-lg shadow-primary/20 active:scale-95" data-session-id="'+s.session_id+'" data-nama="'+s.nama+'" title="Kirim Pesan ke Siswa"><i class="fas fa-paper-plane"></i></button>' +
                             actionHtml +
                            '</div>'
                        ]);
                    });
                    dtRender.draw(false);
                }
            } catch (e) {}
        }, 5000);
    });

    $(document).on('click', '.btn-generate-otp', async function() {
        const sessionId = $(this).data('session-id');
        $('#modalSessionId').text(sessionId);
        $('#otpDisplay').text('...');
        $('#otpModal').removeClass('hidden').addClass('flex');

        try {
            const res = await fetch('/api/generate_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sessionId: sessionId, supervisorId: 'admin_1', otpType: 'unlock' })
            });
            const data = await res.json();
            if (data.otp) $('#otpDisplay').text(data.otp);
            else { alert(data.error); closeModal(); }
        } catch (err) { closeModal(); }
    });

    function closeModal() { $('#otpModal').removeClass('flex').addClass('hidden'); }

    $(document).on('click', '.btn-device-info', function() {
        const btn = $(this);
        $('#diModalName').text(btn.data('name'));
        $('#diModalManufacturer').text(btn.data('manufacturer'));
        $('#diModalModel').text(btn.data('model'));
        $('#diModalAndroid').text(btn.data('android'));
        $('#diModalSdk').text(btn.data('sdk'));
        $('#deviceInfoModal').removeClass('hidden').addClass('flex');
    });

    function closeDeviceInfoModal() { $('#deviceInfoModal').removeClass('flex').addClass('hidden'); }

    $('#refreshTokenBtn').on('click', async function() {
        const btn = $(this);
        btn.addClass('animate-spin');
        try {
            const res = await fetch('/api/refresh_master_token.php');
            const data = await res.json();
            if (data.success) {
                $('#masterTokenDisplay').text(data.new_token).addClass('text-yellow-400');
                setTimeout(() => $('#masterTokenDisplay').removeClass('text-yellow-400'), 1000);
            }
        } finally { btn.removeClass('animate-spin'); }
    });

    // Message Logic
    $(document).on('click', '.btn-send-msg', function() {
        const btn = $(this);
        $('#msgSiswaName').text(btn.data('nama'));
        $('#msgSessionId').val(btn.data('session-id'));
        $('#msgContent').val('');
        $('#msgModal').removeClass('hidden').addClass('flex');
    });

    function closeMsgModal() { $('#msgModal').removeClass('flex').addClass('hidden'); }

    $('#sendAnnouncementBtn').on('click', async function() {
        const btn = $(this);
        const sid = $('#msgSessionId').val();
        const msg = $('#msgContent').val();

        if(!msg.trim()) return alert('Pesan tidak boleh kosong!');

        btn.prop('disabled', true).addClass('opacity-50');
        try {
            const res = await fetch('api/send_announcement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sessionId: sid, message: msg })
            });
            const data = await res.json();
            if (data.success) {
                alert('Pesan terkirim 🚀');
                closeMsgModal();
            } else {
                alert('Gagal: ' + data.error);
            }
        } catch (e) {
            alert('Kesalahan jaringan / server error.');
        } finally {
            btn.prop('disabled', false).removeClass('opacity-50');
        }
    });
</script>

<?php include 'footer.php'; ?>
