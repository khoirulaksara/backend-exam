<?php
/**
 * useragent.php - Contoh Verifikasi User-Agent di Sisi Server
 * Gunakan script ini sebagai referensi untuk mengamankan CBT Pak.
 */

// 1. Ambil User Agent dari browser pengakses
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// 2. Tentukan kunci rahasia yang kita tanam di aplikasi Android
$secretKey = "ArchangelAgent";

// 3. Cek apakah kunci tersebut ada di dalam User Agent
$isValid = (strpos($userAgent, $secretKey) !== false);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akses Aplikasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center p-6">

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
        <?php if ($isValid): ?>
            <!-- JIKA AKSES DARI APLIKASI RESMI -->
            <div class="mb-6 inline-block p-4 bg-green-100 rounded-full">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Akses Diizinkan</h1>
            <p class="text-slate-600 mb-6">Selamat Datang! Anda menggunakan aplikasi resmi ujian SMK Ma'arif 05 Kotagajah.</p>
            <div class="bg-green-50 p-4 rounded-lg text-xs font-mono text-green-700 text-left overflow-x-auto">
                <strong>Detected UA:</strong><br>
                <?= htmlspecialchars($userAgent) ?>
            </div>

        <?php else: ?>
            <!-- JIKA AKSES DARI BROWSER BIASA (Chrome/Safari/dll) -->
            <div class="mb-6 inline-block p-4 bg-red-100 rounded-full">
                <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Akses Ditolak!</h1>
            <p class="text-slate-600 mb-6 font-medium text-red-600">Mohon akses melalui aplikasi resmi ujian SMK Ma'arif 05 Kotagajah.</p>
            <p class="text-slate-500 text-sm mb-6">Browser Anda tidak memiliki izin untuk mengakses halaman ujian ini demi keamanan.</p>
            
            <div class="bg-slate-50 p-4 rounded-lg text-xs font-mono text-slate-500 text-left overflow-x-auto">
                <strong>Detected UA:</strong><br>
                <?= htmlspecialchars($userAgent) ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 pt-6 border-t border-slate-100">
            <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Secure Exam System v2.0</p>
        </div>
    </div>

</body>
</html>
