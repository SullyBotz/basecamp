<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basecamp Engineering - Forum Komunitas Digital & Pusat Karir FT UP</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js CDN for interactive UI state -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(59, 130, 246, 0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white" x-data="{ mobileMenuOpen: false }">

    <!-- ========================================== -->
    <!-- 1. NAVIGATION BAR                          -->
    <!-- ========================================== -->
    <header class="fixed top-0 left-0 right-0 z-50 glass-header border-b border-slate-200/80 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Logo / Brand -->
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-11 h-11 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform duration-300 -rotate-2">
                    <svg class="w-6 h-6 rotate-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V12m0 0h4m-4 0H7" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-black tracking-widest text-blue-600 uppercase block">Fakultas Teknik</span>
                    <span class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-1">
                        BASECAMP <span class="text-blue-600">ENGINEERING</span>
                    </span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 font-bold text-xs uppercase tracking-wider text-slate-600">
                <a href="#tentang" class="hover:text-blue-600 transition-colors">Tentang Sistem</a>
                <a href="#fitur" class="hover:text-blue-600 transition-colors">Fitur Utama</a>
                <a href="#panduan" class="hover:text-blue-600 transition-colors">Panduan Fitur</a>
                <a href="#aktor" class="hover:text-blue-600 transition-colors">Peran Pengguna</a>
            </nav>

            <!-- Action Buttons -->
            <div class="hidden sm:flex items-center gap-3">
                <a href="/login" class="px-5 py-2.5 rounded-xl border-2 border-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider hover:border-blue-600 hover:text-blue-600 transition-all">
                    Masuk
                </a>
                <a href="/register" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-blue-700 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all">
                    Daftar Akun
                </a>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-600 hover:text-blue-600 focus:outline-none">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!mobileMenuOpen">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="mobileMenuOpen" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Nav -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden border-b border-slate-200 bg-white px-6 py-6 space-y-4" style="display: none;">
            <a @click="mobileMenuOpen = false" href="#tentang" class="block text-sm font-bold uppercase text-slate-700 hover:text-blue-600">Tentang Sistem</a>
            <a @click="mobileMenuOpen = false" href="#fitur" class="block text-sm font-bold uppercase text-slate-700 hover:text-blue-600">Fitur Utama</a>
            <a @click="mobileMenuOpen = false" href="#panduan" class="block text-sm font-bold uppercase text-slate-700 hover:text-blue-600">Panduan Fitur</a>
            <a @click="mobileMenuOpen = false" href="#aktor" class="block text-sm font-bold uppercase text-slate-700 hover:text-blue-600">Peran Pengguna</a>
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-3">
                <a href="/login" class="w-full py-3 text-center rounded-xl border border-slate-300 font-bold text-xs uppercase text-slate-700">Masuk</a>
                <a href="/register" class="w-full py-3 text-center rounded-xl bg-blue-600 font-bold text-xs uppercase text-white shadow-md shadow-blue-500/20">Daftar Akun</a>
            </div>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- 2. HERO SECTION                            -->
    <!-- ========================================== -->
    <section class="relative pt-32 pb-20 md:pt-44 md:pb-32 overflow-hidden bg-grid-pattern">
        <!-- Background Gradient Blobs -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 right-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            
            <!-- Subtitle Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs sm:text-sm font-bold mb-6 animate-pulse">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span>Selamat datang di Forum Komunitas Digital Fakultas Teknik</span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl sm:text-6xl md:text-7xl font-black text-slate-900 tracking-tight leading-none mb-6">
                BASECAMP <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-800">ENGINEERING</span>
            </h1>

            <!-- Supporting Tagline -->
            <p class="max-w-3xl mx-auto text-base sm:text-lg md:text-xl text-slate-600 font-medium leading-relaxed mb-10">
                Wadah Terpadu Diskusi Komunitas, Pusat Karir, Jejaring Alumni, dan Informasi Resmi Fakultas Teknik <span class="font-bold text-slate-800">Universitas Pahlawan Tuanku Tambusai</span>.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="/register" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-blue-600 text-white font-extrabold text-sm uppercase tracking-wider hover:bg-blue-700 shadow-xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 transition-all flex items-center justify-center gap-3">
                    <span>Jelajahi & Daftar Sekarang</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="/login" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white border border-slate-200 text-slate-700 font-extrabold text-sm uppercase tracking-wider hover:bg-slate-50 hover:border-slate-300 shadow-sm hover:shadow transition-all text-center">
                    Masuk ke Akun
                </a>
            </div>

            <!-- Key Feature Quick Badges -->
            <div class="pt-10 border-t border-slate-200/80 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm text-left">
                    <p class="text-2xl font-black text-blue-600">100%</p>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-0.5">Terverifikasi Kaprodi</p>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm text-left">
                    <p class="text-2xl font-black text-emerald-600">WhatsApp</p>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-0.5">Notifikasi API Fonnte</p>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm text-left">
                    <p class="text-2xl font-black text-indigo-600">Pusat Karir</p>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-0.5">Loker Alumni & Mitra</p>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm text-left">
                    <p class="text-2xl font-black text-amber-600">Moderasi</p>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-0.5">Kontrol Dekan & Kaprodi</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ========================================== -->
    <!-- 3. ABOUT SYSTEM SECTION                    -->
    <!-- ========================================== -->
    <section id="tentang" class="py-20 bg-white border-y border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Text Explanation -->
                <div class="lg:col-span-6 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 text-xs font-black uppercase tracking-widest">
                        Penjelasan Sistem
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                        Menghubungkan Civitas Akademika Fakultas Teknik dalam Satu Pintasan Digital.
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-sm sm:text-base">
                        <strong class="text-slate-800">BASECAMP ENGINEERING</strong> dirancang khusus sebagai solusi atas tantangan tersebarnya informasi dan terbatasnya jejaring karir di lingkungan fakultas. Sistem ini menjembatani 4 aktor utama: <span class="font-bold text-blue-600">Dekan, Kaprodi, Mahasiswa,</span> dan <span class="font-bold text-blue-600">Alumni</span>.
                    </p>

                    <!-- Feature List -->
                    <div class="space-y-4 pt-2">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 font-bold">1</div>
                            <div>
                                <h4 class="text-base font-bold text-slate-900">Validasi & Verifikasi Spesifik Prodi</h4>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Setiap pendaftar difilter otomatis agar verifikasi akun dan notifikasi WhatsApp hanya ditujukan ke Kaprodi dari program studi yang dipilih.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 font-bold">2</div>
                            <div>
                                <h4 class="text-base font-bold text-slate-900">Pusat Karir & Pengemasan Loker Valid</h4>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Alumni dapat membagikan peluang karir lengkap dengan gambar brosur. Loker akan diperiksa oleh Kaprodi/Dekan sebelum dipublikasikan.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 font-bold">3</div>
                            <div>
                                <h4 class="text-base font-bold text-slate-900">Moderasi & Kontrol Konten Penuh</h4>
                                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Dekan dan Kaprodi memiliki wewenang khusus untuk melakukan moderasi (penghapusan) terhadap postingan yang melanggar norma akademik.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Visual Card Illustration -->
                <div class="lg:col-span-6 relative">
                    <div class="bg-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl relative overflow-hidden border border-slate-800">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl"></div>
                        
                        <!-- Header Mockup -->
                        <div class="flex items-center justify-between pb-6 border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <span class="text-xs font-mono text-slate-400">Live Notification Hub (Fonnte)</span>
                        </div>

                        <!-- Notification Preview Box -->
                        <div class="mt-6 space-y-4">
                            <!-- Notification Item 1 -->
                            <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700/80 flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                </div>
                                <div class="text-xs">
                                    <p class="font-bold text-emerald-400">Pemberitahuan WhatsApp Kaprodi</p>
                                    <p class="text-slate-300 mt-1">"Halo Pak Kaprodi, pengguna baru bernama <strong>Budi Santoso</strong> mendaftar sebagai Mahasiswa Aktif Teknik Informatika..."</p>
                                    <span class="text-[10px] text-slate-500 mt-2 block">Otomatis Terintegrasi via Fonnte API</span>
                                </div>
                            </div>

                            <!-- Notification Item 2 -->
                            <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700/80 flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="text-xs">
                                    <p class="font-bold text-blue-400">Pusat Karir Terverifikasi</p>
                                    <p class="text-slate-300 mt-1">Lowongan "Software Engineer Intern" telah diverifikasi oleh Dekan & siap dilamar oleh Mahasiswa.</p>
                                    <span class="text-[10px] text-slate-500 mt-2 block">Status: Published</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================== -->
    <!-- 4. INTERACTIVE TUTORIAL SECTION            -->
    <!-- ========================================== -->
    <section id="panduan" class="py-20 bg-slate-50" x-data="{ activeTab: 'daftar' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 text-xs font-black uppercase tracking-widest mb-3">
                    Panduan Penggunaan
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Cara Kerja & Tutorial Fitur Platform
                </h2>
                <p class="text-slate-600 mt-3 text-sm sm:text-base">
                    Pilih modul fitur di bawah untuk melihat tata cara penggunaan dan alur kerja sistem.
                </p>
            </div>

            <!-- Tab Navigation Buttons -->
            <div class="flex items-center justify-center gap-2 overflow-x-auto pb-4 mb-8 no-scrollbar">
                <button @click="activeTab = 'daftar'" :class="activeTab === 'daftar' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'bg-white text-slate-600 hover:bg-slate-100'" class="px-5 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider whitespace-nowrap transition-all">
                    1. Pendaftaran & WA
                </button>
                <button @click="activeTab = 'karir'" :class="activeTab === 'karir' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'bg-white text-slate-600 hover:bg-slate-100'" class="px-5 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider whitespace-nowrap transition-all">
                    2. Pusat Karir
                </button>
                <button @click="activeTab = 'diskusi'" :class="activeTab === 'diskusi' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'bg-white text-slate-600 hover:bg-slate-100'" class="px-5 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider whitespace-nowrap transition-all">
                    3. Komunitas & Diskusi
                </button>
                <button @click="activeTab = 'pesan'" :class="activeTab === 'pesan' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'bg-white text-slate-600 hover:bg-slate-100'" class="px-5 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider whitespace-nowrap transition-all">
                    4. Pesan Langsung
                </button>
                <button @click="activeTab = 'moderasi'" :class="activeTab === 'moderasi' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25' : 'bg-white text-slate-600 hover:bg-slate-100'" class="px-5 py-3 rounded-2xl font-bold text-xs uppercase tracking-wider whitespace-nowrap transition-all">
                    5. Moderasi Postingan
                </button>
            </div>

            <!-- Tab Content Cards -->
            <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-xl">
                
                <!-- Tab 1: Pendaftaran -->
                <div x-show="activeTab === 'daftar'" x-transition class="space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-blue-100 text-blue-600 font-black flex items-center justify-center">01</span>
                        <h3 class="text-xl font-bold text-slate-900">Alur Pendaftaran Akun & Verifikasi Otomatis WhatsApp</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-blue-600 uppercase">Langkah 1</span>
                            <h4 class="font-bold text-slate-900 mt-1">Isi Form Ringkas</h4>
                            <p class="text-xs text-slate-500 mt-2">Calon pengguna mendaftar dengan memilih status (Mahasiswa/Alumni) dan Program Studi yang sesuai.</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-blue-600 uppercase">Langkah 2</span>
                            <h4 class="font-bold text-slate-900 mt-1">Trigger Notif Fonnte</h4>
                            <p class="text-xs text-slate-500 mt-2">Sistem menyaring data dan mengirim notifikasi WhatsApp otomatis HANYA ke Kaprodi prodi terkait.</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-blue-600 uppercase">Langkah 3</span>
                            <h4 class="font-bold text-slate-900 mt-1">Approval Kaprodi</h4>
                            <p class="text-xs text-slate-500 mt-2">Kaprodi menyetujui akun melalui menu "Manajemen User". Setelah disetujui, akun baru bisa digunakan untuk login.</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Pusat Karir -->
                <div x-show="activeTab === 'karir'" x-transition class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600 font-black flex items-center justify-center">02</span>
                        <h3 class="text-xl font-bold text-slate-900">Alur Publikasi & Verifikasi Lowongan Kerja</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-emerald-600 uppercase">Langkah 1</span>
                            <h4 class="font-bold text-slate-900 mt-1">Post Lowongan</h4>
                            <p class="text-xs text-slate-500 mt-2">Alumni atau Mahasiswa mengisi formulir lowongan kerja lengkap dengan gambar/brosur dan link pendaftaran.</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-emerald-600 uppercase">Langkah 2</span>
                            <h4 class="font-bold text-slate-900 mt-1">Holding & Queue</h4>
                            <p class="text-xs text-slate-500 mt-2">Postingan masuk ke daftar antrean bertanda <em>"Menunggu Verifikasi"</em> (warna oranye) agar tidak asal publish.</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-emerald-600 uppercase">Langkah 3</span>
                            <h4 class="font-bold text-slate-900 mt-1">Verifikasi Pimpinan</h4>
                            <p class="text-xs text-slate-500 mt-2">Kaprodi atau Dekan memeriksa kelayakan. Jika disetujui, loker ter-publish dan notifikasi dikirim ke civitas.</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Diskusi -->
                <div x-show="activeTab === 'diskusi'" x-transition class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-600 font-black flex items-center justify-center">03</span>
                        <h3 class="text-xl font-bold text-slate-900">Interaksi Komunitas & Diskusi Berjenjang</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600">
                        Setiap pengguna dapat membuat postingan umum di beranda, memberikan apreasiasi tombol <strong>Like</strong>, serta berdiskusi secara interaktif melalui sistem komentar berjenjang (nested reply).
                    </p>
                </div>

                <!-- Tab 4: Pesan -->
                <div x-show="activeTab === 'pesan'" x-transition class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-600 font-black flex items-center justify-center">04</span>
                        <h3 class="text-xl font-bold text-slate-900">Direct Message (Pesan Langsung)</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600">
                        Fasilitas komunikasi privat antar civitas akademika. Mahasiswa dapat berkonsultasi karir secara langsung dengan alumni yang telah bekerja tanpa harus mengumbar nomor kontak pribadi.
                    </p>
                </div>

                <!-- Tab 5: Moderasi -->
                <div x-show="activeTab === 'moderasi'" x-transition class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 font-black flex items-center justify-center">05</span>
                        <h3 class="text-xl font-bold text-slate-900">Fitur Moderasi Konten (Dekan & Kaprodi)</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600">
                        Untuk menjaga iklim akademik yang sehat, Dekan dan Kaprodi dibekali hak khusus untuk **menghapus postingan atau komentar** pengguna lain apabila dinilai mengandung ujaran kebencian, spam, atau materi tidak layak.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- ========================================== -->
    <!-- 5. 4 ROLES SHOWCASE SECTION                -->
    <!-- ========================================== -->
    <section id="aktor" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 text-xs font-black uppercase tracking-widest mb-3">
                    Hak Akses Terstruktur
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    4 Peran Pengguna Dalam Sistem
                </h2>
                <p class="text-slate-600 mt-3 text-sm sm:text-base">
                    Sistem membagi hak akses secara spesifik sesuai wewenang dan tanggung jawab di fakultas.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Role 1: Dekan -->
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:border-blue-500 hover:shadow-xl transition-all group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white font-bold flex items-center justify-center mb-6 text-xl shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform">
                        👔
                    </div>
                    <h3 class="text-xl font-black text-slate-900">DEKAN</h3>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-widest mt-1">Administrator Pusat</p>
                    <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                        Memiliki akses tertinggi untuk memantau statistik seluruh prodi, menerbitkan pengumuman resmi fakultas, serta melakukan moderasi penuh.
                    </p>
                </div>

                <!-- Role 2: Kaprodi -->
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:border-blue-500 hover:shadow-xl transition-all group">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white font-bold flex items-center justify-center mb-6 text-xl shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                        👨‍🏫
                    </div>
                    <h3 class="text-xl font-black text-slate-900">KAPRODI</h3>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-1">Operator Verifikasi Prodi</p>
                    <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                        Menerima notifikasi WhatsApp pendaftaran, memverifikasi akun pendaftar prodinya, serta menyetujui info loker yang masuk.
                    </p>
                </div>

                <!-- Role 3: Mahasiswa -->
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:border-blue-500 hover:shadow-xl transition-all group">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white font-bold flex items-center justify-center mb-6 text-xl shadow-lg shadow-indigo-500/30 group-hover:scale-110 transition-transform">
                        🎓
                    </div>
                    <h3 class="text-xl font-black text-slate-900">MAHASISWA</h3>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mt-1">Pengguna Aktif</p>
                    <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                        Mengakses informasi lowongan karir terverifikasi, berdiskusi di forum komunitas, dan membangun jejaring dengan para alumni.
                    </p>
                </div>

                <!-- Role 4: Alumni -->
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:border-blue-500 hover:shadow-xl transition-all group">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white font-bold flex items-center justify-center mb-6 text-xl shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                        💼
                    </div>
                    <h3 class="text-xl font-black text-slate-900">ALUMNI</h3>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mt-1">Kontributor Karir</p>
                    <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                        Membagikan informasi peluang kerja dari dunia industri, mencantumkan riwayat skripsi/pembimbing, dan membimbing adik tingkat.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- ========================================== -->
    <!-- 6. FOOTER SECTION                          -->
    <!-- ========================================== -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                
                <div>
                    <span class="text-lg font-black text-white tracking-tight">BASECAMP <span class="text-blue-500">ENGINEERING</span></span>
                    <p class="text-xs text-slate-500 mt-1">Fakultas Teknik Universitas Pahlawan Tuanku Tambusai</p>
                </div>

                <div class="text-xs text-slate-500">
                    &copy; 2026 Basecamp Engineering. All rights reserved.
                </div>

                <div class="flex items-center gap-4 text-xs font-bold uppercase">
                    <a href="/login" class="hover:text-white transition">Masuk</a>
                    <span class="text-slate-700">•</span>
                    <a href="/register" class="hover:text-white transition">Daftar</a>
                </div>

            </div>
        </div>
    </footer>

</body>
</html>