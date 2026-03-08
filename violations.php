<?php
// violations.php - Violations Log
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

require_once 'api/config.php';

// Fetch violations
$stmt = $pdo->query("SELECT v.*, s.device_id FROM violations v 
    JOIN sessions s ON v.session_id = s.session_id 
    ORDER BY v.timestamp DESC LIMIT 100");
$violations = $stmt->fetchAll();

// Fetch student names
$studentNames = [];
if (count($violations) > 0) {
    $nisList = array_unique(array_column($violations, 'session_id'));
    if (!empty($nisList)) {
        $inQuery = implode(',', array_fill(0, count($nisList), '?'));
        try {
            $cbtStmt = $pdo_cbt->prepare("SELECT no_peserta, nama FROM siswa WHERE no_peserta IN ($inQuery)");
            $cbtStmt->execute(array_values($nisList));
            $students = $cbtStmt->fetchAll();
            foreach ($students as $s) {
                $studentNames[$s['no_peserta']] = $s['nama'];
            }
        } catch (Exception $e) {}
    }
}

$page_title = "Violations Log";
include 'header.php';
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Log Pelanggaran</h1>
        <p class="text-slate-500 font-medium">Rekaman aktivitas mencurigakan siswa selama ujian.</p>
    </div>
    <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        Last 100 Events
    </div>
</div>

<div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="violationsTable" class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-slate-100">
                    <th class="pb-4 pt-2 px-4">Waktu</th>
                    <th class="pb-4 pt-2 px-4">Siswa / NIS</th>
                    <th class="pb-4 pt-2 px-4">Jenis</th>
                    <th class="pb-4 pt-2 px-4">Durasi</th>
                    <th class="pb-4 pt-2 px-4 text-right">Skor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $log): ?>
                    <?php $isSevere = (!empty($log['duration_seconds']) && $log['duration_seconds'] >= 10); ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors <?= $isSevere ? 'bg-red-50/30' : '' ?>">
                        <td class="p-4">
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">
                                <?= date('H:i:s', strtotime($log['timestamp'])) ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($studentNames[$log['session_id']] ?? 'Unknown') ?></span>
                                <span class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($log['session_id']) ?></span>
                            </div>
                        </td>
                        <td class="p-4">
                            <?php 
                            $type = $log['event_type'];
                            $class = "bg-slate-100 text-slate-600";
                            if (str_contains($type, 'keluar')) $class = $isSevere ? "bg-red-500 text-white" : "bg-orange-500/10 text-orange-600";
                            if ($type === 'UNPIN' || $type === 'MULTIWINDOW') $class = "bg-red-600 text-white";
                            if ($type === 'PERMINTAAN_KELUAR') $class = "bg-purple-500/10 text-purple-600";
                            ?>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tight shadow-sm <?= $class ?>">
                                <?= htmlspecialchars($type) ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <?php if (!empty($log['duration_seconds'])): ?>
                                <span class="text-xs font-bold <?= $isSevere ? 'text-red-600' : 'text-slate-500' ?>">
                                    <?= $log['duration_seconds'] ?>s
                                </span>
                            <?php else: ?>
                                <span class="text-slate-300 font-mono text-[10px]">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right">
                            <span class="text-sm font-black <?= $log['risk_value'] > 0 ? 'text-red-500' : 'text-slate-400' ?>">
                                +<?= $log['risk_value'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#violationsTable').DataTable({
            "pageLength": 50,
            "order": [[0, "desc"]],
            "dom": '<"flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4"f>rt<"flex flex-col md:flex-row md:items-center justify-between gap-4 mt-6"ip>',
            "language": {
                "search": "",
                "searchPlaceholder": "Filter aktivitas...",
                "info": "Menampilkan <span class='font-bold text-slate-900'>_START_ - _END_</span> dari _TOTAL_ event"
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
