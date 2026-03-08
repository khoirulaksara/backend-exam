<?php
// weton.php - Weton Calculator for Supervisor
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

function hitungWetonDariTanggal(string $tanggal): array
{
    $tz   = new DateTimeZone('Asia/Jakarta');
    $date = new DateTime($tanggal, $tz);
    $indexHari = (int) $date->format('w');

    $namaHari = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    $hari = $namaHari[$indexHari];
    $ref = new DateTime('1900-01-01', $tz);
    $detikSelisih = $date->getTimestamp() - $ref->getTimestamp();
    $selisihHari  = intdiv($detikSelisih, 86400);
    $siklusPasaran = ['Pahing', 'Pon', 'Wage', 'Kliwon', 'Legi'];

    $mod = function (int $a, int $n): int {
        $r = $a % $n;
        return $r < 0 ? $r + $n : $r;
    };

    $idxPasaran = $mod($selisihHari, 5);
    $pasaran    = $siklusPasaran[$idxPasaran];

    $neptuHari = ['Minggu' => 5, 'Senin' => 4, 'Selasa' => 3, 'Rabu' => 7, 'Kamis' => 8, 'Jumat' => 6, 'Sabtu' => 9];
    $neptuPasaran = ['Legi' => 5, 'Pahing' => 9, 'Pon' => 7, 'Wage' => 4, 'Kliwon' => 8];

    $nHari     = $neptuHari[$hari] ?? 0;
    $nPasaran  = $neptuPasaran[$pasaran] ?? 0;
    $nTotal    = $nHari + $nPasaran;

    return [
        'tanggal'       => $date->format('d F Y'),
        'hari'          => $hari,
        'pasaran'       => $pasaran,
        'neptu_hari'    => $nHari,
        'neptu_pasaran' => $nPasaran,
        'neptu_total'   => $nTotal,
    ];
}

$wetonResult = null;
if (isset($_GET['tanggal']) && !empty($_GET['tanggal'])) {
    $wetonResult = hitungWetonDariTanggal($_GET['tanggal']);
}

$page_title = "Weton Calculator";
include 'header.php';
?>

<div class="mb-10">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Cek Weton Sesi</h1>
    <p class="text-slate-500 font-medium">Hitung hari pasaran Jawa untuk keperluan administratif sesi.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Calculator Card -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/50">
            <h3 class="font-black text-slate-900 flex items-center gap-3 uppercase tracking-wider text-sm">
                <i class="fas fa-calendar-alt text-primary"></i>
                Pilih Tanggal Sesi
            </h3>
        </div>
        <div class="p-10">
            <form method="GET" action="" class="space-y-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Tanggal Ujian / Sesi</label>
                    <input type="date" name="tanggal" value="<?= $_GET['tanggal'] ?? date('Y-m-d') ?>" class="w-full bg-white border border-slate-100 rounded-[1.5rem] py-5 px-8 text-xl font-black text-slate-800 focus:ring-4 focus:ring-primary/10 focus:outline-none transition-all">
                </div>
                <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white py-5 rounded-[1.5rem] font-black text-sm transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                    HITUNG WETON <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Result Card -->
    <?php if ($wetonResult): ?>
    <div class="bg-primary rounded-[2.5rem] shadow-2xl shadow-primary/20 text-white overflow-hidden p-1 px-1">
        <div class="p-10 h-full flex flex-col justify-between">
            <div class="space-y-1">
                <p class="text-white/50 text-[10px] font-black uppercase tracking-[0.2em]">Hasil Perhitungan</p>
                <h3 class="text-3xl font-black italic"><?= $wetonResult['hari'] ?> <?= $wetonResult['pasaran'] ?></h3>
                <p class="text-white/60 font-bold"><?= $wetonResult['tanggal'] ?></p>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mt-10">
                <div class="bg-white/10 backdrop-blur-md rounded-3xl p-6 text-center border border-white/5">
                    <p class="text-[9px] font-black text-white/50 uppercase mb-2">Neptu H</p>
                    <p class="text-3xl font-black"><?= $wetonResult['neptu_hari'] ?></p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-3xl p-6 text-center border border-white/5">
                    <p class="text-[9px] font-black text-white/50 uppercase mb-2">Neptu P</p>
                    <p class="text-3xl font-black"><?= $wetonResult['neptu_pasaran'] ?></p>
                </div>
                <div class="bg-white/20 backdrop-blur-md rounded-3xl p-6 text-center border border-white/10">
                    <p class="text-[9px] font-black text-white/50 uppercase mb-2">Total</p>
                    <p class="text-3xl font-black text-yellow-300"><?= $wetonResult['neptu_total'] ?></p>
                </div>
            </div>
            
            <div class="mt-10 p-6 bg-black/10 rounded-3xl border border-white/5">
                <p class="text-[11px] font-medium leading-relaxed opacity-80">
                    <i class="fas fa-info-circle mr-2"></i>
                    Gunakan neptu total untuk penentuan shift pengawas atau pembagian ruang jika diperlukan sesuai kearifan lokal.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-slate-100/50 rounded-[2.5rem] border-4 border-dashed border-slate-200 flex flex-col items-center justify-center p-10 text-center text-slate-400">
        <i class="fas fa-magic text-4xl mb-4"></i>
        <p class="font-bold text-sm tracking-tight uppercase">Masukkan tanggal untuk <br>melihat neptu & pasaran</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
