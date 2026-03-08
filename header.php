<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e293b">
    <title><?= $page_title ?? 'Supervisor Dashboard' ?> - Archangel v2</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Frameworks -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        dark: '#0f172a',
                        'slate-850': '#1e293b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#f8fafc] flex h-screen overflow-hidden text-[#1e293b]">

    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden md:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-dark text-white flex flex-col fixed inset-y-0 left-0 z-50 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-out shadow-2xl">
        <div class="p-6 border-b border-white/5 flex justify-between items-center bg-slate-850/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/20 rounded-xl flex items-center justify-center border border-primary/30">
                    <i class="fas fa-shield-halved text-primary text-xl"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg tracking-tight">ARCHANGEL</h1>
                    <p class="text-[10px] text-primary font-bold uppercase tracking-[0.2em] -mt-1">Supervisor Hub</p>
                </div>
            </div>
            <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto mt-4">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            
            <a href="index.php" class="sidebar-link flex items-center gap-3.5 p-3.5 rounded-xl <?= $current_page == 'index.php' ? 'active' : 'text-gray-400 hover:text-white' ?>">
                <i class="fas fa-chart-line w-5"></i>
                <span class="font-semibold text-sm">Live Sessions</span>
            </a>
            
            <a href="violations.php" class="sidebar-link flex items-center gap-3.5 p-3.5 rounded-xl <?= $current_page == 'violations.php' ? 'active' : 'text-gray-400 hover:text-white' ?>">
                <i class="fas fa-user-shield w-5"></i>
                <span class="font-semibold text-sm">Violations Log</span>
            </a>
            
            <a href="settings.php" class="sidebar-link flex items-center gap-3.5 p-3.5 rounded-xl <?= $current_page == 'settings.php' ? 'active' : 'text-gray-400 hover:text-white' ?>">
                <i class="fas fa-cog w-5"></i>
                <span class="font-semibold text-sm">System Settings</span>
            </a>
            
            <div class="pt-6 pb-2">
                <p class="px-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Utility</p>
            </div>
            
            <a href="weton.php" class="sidebar-link flex items-center gap-3.5 p-3.5 rounded-xl <?= $current_page == 'weton.php' ? 'active' : 'text-gray-400 hover:text-white' ?>">
                <i class="fas fa-calendar-alt w-5"></i>
                <span class="font-semibold text-sm">Cek Weton Sesi</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-white/5 bg-slate-850/30">
            <div class="bg-white/5 rounded-2xl p-4 flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-tr from-primary to-indigo-500 rounded-full flex items-center justify-center font-bold">A</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold truncate">Admin Supervisor</p>
                    <p class="text-[10px] text-gray-500 truncate">Sesi Aktif: <?= date('d M Y') ?></p>
                </div>
            </div>
            <a href="index.php?logout=1" class="flex items-center justify-center gap-2 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white p-3.5 rounded-xl w-full transition-all duration-300 font-bold text-sm">
                <i class="fas fa-power-off"></i>
                Keluar Sistem
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl -mr-48 -mt-48 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl -ml-48 -mb-48 pointer-events-none"></div>

        <!-- Mobile Header -->
        <header class="md:hidden bg-dark text-white flex items-center justify-between p-4 shadow-xl z-30 flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="fas fa-shield-halved text-primary"></i>
                <h1 class="font-bold text-lg tracking-tight">ARCHANGEL</h1>
            </div>
            <button id="openSidebarBtn" class="text-gray-300 hover:text-white focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 p-5 md:p-10 overflow-y-auto w-full relative z-10">
