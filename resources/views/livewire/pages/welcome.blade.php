<?php
use App\Models\Job;
use App\Models\User;
use App\Models\ProgramStudi;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.landing')] class extends Component {
    public function with()
    {
        return [
            'totalJobs'    => Job::where('is_verified', true)->count(),
            'totalAlumni'  => User::where('role', 'alumni')->count(),
            'totalProdi'   => ProgramStudi::count(),
            'latestJobs'   => Job::where('is_verified', true)->latest()->take(3)->get(),
        ];
    }
};
?>

{{-- ⚠️ KUNCI UTAMA: SELURUH HALAMAN WAJIB DI DALAM 1 DIV INI --}}
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20 text-gray-800 font-sans antialiased selection:bg-blue-600 selection:text-white">

    {{-- NAVBAR --}}
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-black shadow-lg shadow-blue-200 -rotate-3">
                    BE
                </div>
                <div>
                    <span class="text-base font-black tracking-wider text-gray-900 block leading-none">BASECAMP</span>
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Engineering</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('pages.welcome') }}" class="px-6 py-2.5 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-5 py-2.5 text-gray-600 hover:text-blue-600 font-bold text-xs uppercase tracking-wider transition">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="px-6 py-2.5 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                        Daftar Akun
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- HERO SECTION --}}
    <section class="max-w-7xl mx-auto px-6 py-20 text-center relative overflow-hidden">
        <div class="absolute inset-0 -z-10 flex items-center justify-center opacity-30 pointer-events-none">
            <div class="w-[500px] h-[500px] bg-blue-400/20 rounded-full blur-3xl"></div>
            <div class="w-[400px] h-[400px] bg-purple-400/20 rounded-full blur-3xl"></div>
        </div>

        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-xs font-black uppercase tracking-widest mb-6 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
            Platform Komunitas & Karir Resmi Fakultas Teknik
        </div>

        <h1 class="text-4xl md:text-6xl font-black text-gray-900 uppercase tracking-tight mb-6 max-w-4xl mx-auto leading-tight">
            Selamat Datang di Forum Komunitas Digital <span class="text-blue-600">Fakultas Teknik</span>
        </h1>

        <div class="my-8">
            <h2 class="text-3xl md:text-5xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 uppercase drop-shadow-sm">
                BASECAMP ENGINEERING
            </h2>
        </div>

        <p class="text-base md:text-lg text-gray-500 font-medium max-w-2xl mx-auto mb-10">
            Pusat interaksi akademik, jejaring alumni, serta gerbang informasi lowongan pekerjaan terpadu yang didukung oleh otomatisasi notifikasi cerdas berbasis WhatsApp.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 shadow-xl shadow-blue-200 transition transform hover:-translate-y-0.5">
                Gabung Sekarang &rarr;
            </a>
            <a href="#fitur" class="px-8 py-4 bg-white text-gray-700 border border-gray-200 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-50 transition">
                Pelajari Sistem
            </a>
        </div>

        {{-- Statistik Singkat --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mt-20">
            <div class="bg-white/80 backdrop-blur border border-gray-100 p-6 rounded-3xl shadow-sm">
                <p class="text-3xl font-black text-blue-600">{{ $totalAlumni }}+</p>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Alumni Terdaftar</p>
            </div>
            <div class="bg-white/80 backdrop-blur border border-gray-100 p-6 rounded-3xl shadow-sm">
                <p class="text-3xl font-black text-purple-600">{{ $totalJobs }}+</p>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Lowongan Pekerjaan</p>
            </div>
            <div class="bg-white/80 backdrop-blur border border-gray-100 p-6 rounded-3xl shadow-sm">
                <p class="text-3xl font-black text-indigo-600">{{ $totalProdi }}</p>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Program Studi</p>
            </div>
        </div>
    </section>

    {{-- TENTANG SISTEM --}}
    <section id="fitur" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-blue-600 text-xs font-black uppercase tracking-widest">Arsitektur & Fungsi</span>
            <h2 class="text-3xl font-black text-gray-900 uppercase mt-2">Tentang Sistem Basecamp Engineering</h2>
            <p class="text-sm text-gray-500 mt-3 font-medium">
                Sistem ini dirancang khusus untuk menjembatani komunikasi antara mahasiswa aktif, alumni, ketua program studi, dan dekanat dalam satu platform terintegrasi.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group hover:border-blue-200 transition">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center font-black mb-6 text-lg">01</div>
                <h3 class="font-black text-gray-900 text-lg uppercase mb-3">Pusat Karir & Loker</h3>
                <p class="text-xs text-gray-500 leading-relaxed font-medium">
                    Mahasiswa dan alumni dapat berbagi informasi lowongan pekerjaan. Dilengkapi sistem moderasi ketat di mana loker wajib disetujui Kaprodi/Dekan sebelum dipublikasikan ke publik.
                </p>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group hover:border-purple-200 transition">
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center font-black mb-6 text-lg">02</div>
                <h3 class="font-black text-gray-900 text-lg uppercase mb-3">Notifikasi WhatsApp Fonnte</h3>
                <p class="text-xs text-gray-500 leading-relaxed font-medium">
                    Otomatisasi pengiriman pesan instan. Setiap ada pendaftar baru, sistem secara cerdas memfilter program studi dan langsung mengirimkan pesan verifikasi ke nomor WhatsApp Kaprodi terkait.
                </p>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group hover:border-indigo-200 transition">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-black mb-6 text-lg">03</div>
                <h3 class="font-black text-gray-900 text-lg uppercase mb-3">Manajemen Hak Akses</h3>
                <p class="text-xs text-gray-500 leading-relaxed font-medium">
                    Penerapan *Role-Based Access Control* (RBAC) yang membagi fungsi secara tegas bagi Dekan, Kaprodi, Mahasiswa, dan Alumni guna menjaga keamanan data akademik.
                </p>
            </div>
        </div>
    </section>

    {{-- TUTORIAL PENGGUNAAN SETIAP AKTOR --}}
    <section class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100 bg-gray-50/50 rounded-[3rem] my-10">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-purple-600 text-xs font-black uppercase tracking-widest">Panduan Pengguna</span>
            <h2 class="text-3xl font-black text-gray-900 uppercase mt-2">Tutorial Penggunaan Berdasarkan Aktor</h2>
            <p class="text-sm text-gray-500 mt-3 font-medium">Berikut adalah alur penggunaan sistem sesuai dengan tingkat hak akses masing-masing peran:</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- MAHASISWA --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black rounded-lg uppercase">Aktor 1</span>
                    <h3 class="font-black text-gray-900 uppercase">Mahasiswa Aktif</h3>
                </div>
                <ul class="space-y-3 text-xs text-gray-600 font-medium">
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 font-black">1.</span> Mendaftar akun dengan memilih status "Mahasiswa" dan memilih Program Studi.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 font-black">2.</span> Menunggu verifikasi akun yang disetujui oleh Kaprodi program studi.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 font-black">3.</span> Mengakses menu Pusat Karir untuk mencari lowongan pekerjaan dan berinteraksi melalui kolom komentar.
                    </li>
                </ul>
            </div>

            {{-- ALUMNI --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-purple-100 text-purple-700 text-[10px] font-black rounded-lg uppercase">Aktor 2</span>
                    <h3 class="font-black text-gray-900 uppercase">Alumni</h3>
                </div>
                <ul class="space-y-3 text-xs text-gray-600 font-medium">
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 font-black">1.</span> Melakukan registrasi dengan mengisi data kelulusan (Angkatan, Judul Skripsi, & Pembimbing).
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 font-black">2.</span> Membagikan lowongan pekerjaan dari instansi/perusahaan tempat bekerja melalui menu "Bagikan Loker".
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 font-black">3.</span> Membangun relasi profesional dengan sesama alumni dan mahasiswa.
                    </li>
                </ul>
            </div>

            {{-- KAPRODI --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-amber-100 text-amber-700 text-[10px] font-black rounded-lg uppercase">Aktor 3</span>
                    <h3 class="font-black text-gray-900 uppercase">Ketua Program Studi (Kaprodi)</h3>
                </div>
                <ul class="space-y-3 text-xs text-gray-600 font-medium">
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600 font-black">1.</span> Menerima notifikasi WhatsApp instan ketika ada pendaftar baru di program studinya.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600 font-black">2.</span> Melakukan verifikasi akun mahasiswa/alumni pada program studi yang dikelola.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600 font-black">3.</span> Memoderasi, memverifikasi, atau menghapus postingan loker/diskusi yang tidak relevan.
                    </li>
                </ul>
            </div>

            {{-- DEKAN --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-red-100 text-red-700 text-[10px] font-black rounded-lg uppercase">Aktor 4</span>
                    <h3 class="font-black text-gray-900 uppercase">Dekan Fakultas Teknik</h3>
                </div>
                <ul class="space-y-3 text-xs text-gray-600 font-medium">
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-black">1.</span> Memantau ringkasan data statistik seluruh program studi secara menyeluruh di fakultas.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-black">2.</span> Mengelola pengumuman resmi fakultas (*Official Broadcast*).
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-black">3.</span> Memiliki hak akses tertinggi (*bypass*) untuk moderasi seluruh konten di dalam platform.
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="border-t border-gray-100 py-10 bg-white text-center">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
            &copy; {{ date('Y') }} Basecamp Engineering &bull; Fakultas Teknik Universitas Pahlawan Tuanku Tambusai
        </p>
    </footer>

</div> {{-- 👈 PENUTUP DIV UTAMA WAJIB ADA DI PALING BAWAH --}}