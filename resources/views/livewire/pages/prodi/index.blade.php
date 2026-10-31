@section('title', 'Prodi')

<?php
use App\Models\ProgramStudi;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {

    // State untuk modal detail
    public $showDetail = false;
    public ?ProgramStudi $selectedProdi = null;

    // State untuk modal tambah/edit
    public $showFormModal = false;
    public $isEditing = false;
    public $formId;
    
    // 💡 Input jumlah mahasiswa & alumni dihapus dari sini karena akan dihitung otomatis
    public $f_nama, $f_kode, $f_jenjang = 'S1', $f_nama_kaprodi, $f_nip_kaprodi;
    public $f_tahun_berdiri, $f_akreditasi = 'Baik', $f_no_sk_akreditasi;
    public $f_icon, $f_warna = '#6366f1', $f_deskripsi, $f_visi, $f_misi;
    public $f_jumlah_dosen = 0, $f_jumlah_lulusan_tahun_ini = 0; 
    public $f_rata_rata_ipk = 0, $f_rata_rata_masa_studi = 0;
    public $f_email, $f_telepon, $f_website;

    public function with(): array
    {
        return [
            // 💡 PERBAIKAN: Menghitung otomatis dari tabel users berdasarkan role
            'prodis' => ProgramStudi::where('is_active', true)
                ->withCount([
                    'users as total_mahasiswa' => function ($query) {
                        $query->where('role', 'mahasiswa');
                    },
                    'users as total_alumni' => function ($query) {
                        $query->where('role', 'alumni');
                    }
                ])
                ->orderBy('tahun_berdiri')
                ->get(),
        ];
    }

    // ── Buka modal detail ──────────────────────────────────────────
    public function lihatDetail(int $id): void
    {
        // 💡 Hitung juga untuk tampilan detail
        $this->selectedProdi = ProgramStudi::withCount([
            'users as total_mahasiswa' => fn($q) => $q->where('role', 'mahasiswa'),
            'users as total_alumni' => fn($q) => $q->where('role', 'alumni')
        ])->findOrFail($id);
        
        $this->showDetail = true;
    }

    public function tutupDetail(): void
    {
        $this->showDetail = false;
        $this->selectedProdi = null;
    }

    // ── Buka modal form tambah ─────────────────────────────────────
    public function openAddForm(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'dekan', 403, 'Akses ditolak.');

        $this->isEditing = false;
        $this->resetForm();
        $this->showFormModal = true;
    }

    // ── Buka modal form edit ───────────────────────────────────────
    public function openEditForm(int $id): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['dekan', 'kaprodi']), 403, 'Akses ditolak.');

        $prodi = ProgramStudi::findOrFail($id);
        $this->isEditing = true;
        $this->formId = $prodi->id;

        $this->f_nama                     = $prodi->nama;
        $this->f_kode                     = $prodi->kode;
        $this->f_jenjang                  = $prodi->jenjang;
        $this->f_nama_kaprodi             = $prodi->nama_kaprodi;
        $this->f_nip_kaprodi              = $prodi->nip_kaprodi;
        $this->f_tahun_berdiri            = $prodi->tahun_berdiri;
        $this->f_akreditasi               = $prodi->akreditasi;
        $this->f_no_sk_akreditasi         = $prodi->no_sk_akreditasi;
        $this->f_icon                     = $prodi->icon;
        $this->f_warna                    = $prodi->warna;
        $this->f_deskripsi                = $prodi->deskripsi;
        $this->f_visi                     = $prodi->visi;
        $this->f_misi                     = $prodi->misi;
        $this->f_jumlah_dosen             = $prodi->jumlah_dosen;
        $this->f_jumlah_lulusan_tahun_ini = $prodi->jumlah_lulusan_tahun_ini;
        $this->f_rata_rata_ipk            = $prodi->rata_rata_ipk;
        $this->f_rata_rata_masa_studi     = $prodi->rata_rata_masa_studi;
        $this->f_email                    = $prodi->email;
        $this->f_telepon                  = $prodi->telepon;
        $this->f_website                  = $prodi->website;

        $this->showFormModal = true;
    }

    // ── Simpan (tambah atau update) ────────────────────────────────
    public function saveProdi(): void
    {
        if ($this->isEditing) {
            abort_unless(auth()->check() && in_array(auth()->user()->role, ['dekan', 'kaprodi']), 403, 'Akses ditolak.');
        } else {
            abort_unless(auth()->check() && auth()->user()->role === 'dekan', 403, 'Akses ditolak.');
        }

        $uniqueKode = $this->isEditing
            ? 'unique:program_studis,kode,' . $this->formId
            : 'unique:program_studis,kode';

        $this->validate([
            'f_nama'          => 'required|string|max:100',
            'f_kode'          => "required|string|max:10|{$uniqueKode}",
            'f_nama_kaprodi'  => 'required|string|max:150',
            'f_tahun_berdiri' => 'required|integer|digits:4|min:1900|max:' . now()->year,
            'f_akreditasi'    => 'required',
            'f_jumlah_dosen'     => 'nullable|integer|min:0',
            'f_rata_rata_ipk'    => 'nullable|numeric|min:0|max:4',
        ], [
            'f_nama.required'          => 'Nama prodi wajib diisi.',
            'f_kode.required'          => 'Kode prodi wajib diisi.',
            'f_kode.unique'            => 'Kode prodi sudah digunakan.',
            'f_nama_kaprodi.required'  => 'Nama Kaprodi wajib diisi.',
            'f_tahun_berdiri.required' => 'Tahun berdiri wajib diisi.',
            'f_tahun_berdiri.digits'   => 'Tahun harus 4 digit.',
        ]);

        $payload = [
            'nama'                    => $this->f_nama,
            'kode'                    => strtoupper($this->f_kode),
            'jenjang'                 => $this->f_jenjang,
            'nama_kaprodi'            => $this->f_nama_kaprodi,
            'nip_kaprodi'             => $this->f_nip_kaprodi,
            'tahun_berdiri'           => $this->f_tahun_berdiri,
            'akreditasi'              => $this->f_akreditasi,
            'no_sk_akreditasi'        => $this->f_no_sk_akreditasi,
            'icon'                    => $this->f_icon,
            'warna'                   => $this->f_warna,
            'deskripsi'               => $this->f_deskripsi,
            'visi'                    => $this->f_visi,
            'misi'                    => $this->f_misi,
            'jumlah_dosen'            => $this->f_jumlah_dosen ?? 0,
            'jumlah_lulusan_tahun_ini'=> $this->f_jumlah_lulusan_tahun_ini ?? 0,
            'rata_rata_ipk'           => $this->f_rata_rata_ipk ?? 0,
            'rata_rata_masa_studi'    => $this->f_rata_rata_masa_studi ?? 0,
            'email'                   => $this->f_email,
            'telepon'                 => $this->f_telepon,
            'website'                 => $this->f_website,
            
            // Kolom lama kita isi 0 agar database tidak error (karena sekarang pakai data real/dinamis)
            'jumlah_mahasiswa'        => 0,
            'jumlah_alumni'           => 0,
        ];

        if ($this->isEditing) {
            ProgramStudi::find($this->formId)->update($payload);
            $msg = 'Data program studi berhasil diperbarui.';
        } else {
            ProgramStudi::create($payload);
            $msg = 'Program studi baru berhasil ditambahkan.';
        }

        $this->showFormModal = false;
        $this->resetForm();
        $this->dispatch('swal:success', title: 'Berhasil!', text: $msg);
    }

    // ── Hapus prodi ────────────────────────────────────────────────
    public function deleteProdi(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'dekan', 403, 'Akses ditolak.');

        ProgramStudi::findOrFail($id)->update(['is_active' => false]);
        $this->dispatch('swal:success', title: 'Dihapus!', text: 'Program studi telah dinonaktifkan.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'formId', 'f_nama', 'f_kode', 'f_nama_kaprodi', 'f_nip_kaprodi',
            'f_tahun_berdiri', 'f_no_sk_akreditasi', 'f_icon', 'f_deskripsi',
            'f_visi', 'f_misi', 'f_email', 'f_telepon', 'f_website',
        ]);
        $this->f_jenjang                  = 'S1';
        $this->f_akreditasi               = 'Baik';
        $this->f_warna                    = '#6366f1';
        $this->f_jumlah_dosen             = 0;
        $this->f_jumlah_lulusan_tahun_ini = 0;
        $this->f_rata_rata_ipk            = 0;
        $this->f_rata_rata_masa_studi     = 0;
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- ══════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════ --}}
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 mb-1">Fakultas Teknik</p>
            <h1 class="text-3xl font-black text-gray-900 uppercase leading-tight">Program Studi</h1>
            <p class="text-sm text-gray-500 mt-1 font-medium">Universitas Pahlawan Tuanku Tambusai &mdash; Kabupaten Kampar, Riau</p>
        </div>
        
        @if (auth()->check() && auth()->user()->role === 'dekan')
                <button wire:click="openAddForm"
                    class="flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-700 shadow-lg transition whitespace-nowrap self-start md:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Prodi
                </button>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         KARTU PROGRAM STUDI
    ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($prodis as $prodi)
        <div class="group relative bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden cursor-pointer"
             wire:click="lihatDetail({{ $prodi->id }})">

            <div class="h-2 w-full" style="background-color: {{ $prodi->warna }}"></div>

            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-sm border border-gray-100"
                         style="background-color: {{ $prodi->warna }}18">
                        {{ $prodi->icon ?? '🎓' }}
                    </div>
                    <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full tracking-wider {{ $prodi->akreditasi_badge_color }}">
                        Akreditasi {{ $prodi->akreditasi }}
                    </span>
                </div>

                <div class="mb-1">
                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $prodi->jenjang }} &bull; {{ $prodi->kode }}</span>
                </div>
                <h2 class="text-lg font-black text-gray-900 leading-tight group-hover:text-[color:var(--prodi-color)] transition-colors"
                    style="--prodi-color: {{ $prodi->warna }}">
                    {{ $prodi->nama }}
                </h2>

                <div class="mt-3 space-y-1.5">
                    <div class="flex items-center gap-2 text-[11px] text-gray-500">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="font-semibold">{{ $prodi->nama_kaprodi }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-gray-500">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Berdiri sejak <strong class="text-gray-700">{{ $prodi->tahun_berdiri }}</strong></span>
                    </div>
                </div>

                <div class="my-4 border-t border-gray-50"></div>

                {{-- 💡 Statistik Dinamis! --}}
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center">
                        <p class="text-base font-black text-gray-900">{{ number_format($prodi->total_mahasiswa) }}</p>
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Mahasiswa Terverivikasi</p>
                    </div>
                    <div class="text-center border-x border-gray-100">
                        <p class="text-base font-black text-gray-900">{{ $prodi->jumlah_dosen }}</p>
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Dosen</p>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-black text-gray-900">{{ number_format($prodi->total_alumni) }}</p>
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Alumni</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[10px] text-gray-400 font-medium">Klik untuk detail lengkap</span>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════
         MODAL DETAIL PROGRAM STUDI
    ══════════════════════════════════════════════ --}}
    @if ($showDetail && $selectedProdi)
    <div class="fixed inset-0 z-[9999] flex items-start justify-center p-4 pt-10 bg-black/50 backdrop-blur-sm overflow-y-auto"
         wire:click.self="tutupDetail">
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden mb-10">

            <div class="relative p-8 text-white overflow-hidden"
                 style="background-color: {{ $selectedProdi->warna }}">
                <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full opacity-10 bg-white"></div>
                <div class="absolute right-20 -bottom-10 w-56 h-56 rounded-full opacity-10 bg-white"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-4xl shadow-lg">
                            {{ $selectedProdi->icon ?? '🎓' }}
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-70">{{ $selectedProdi->jenjang }} &bull; {{ $selectedProdi->kode }}</p>
                            <h2 class="text-2xl font-black leading-tight">{{ $selectedProdi->nama }}</h2>
                            <p class="text-sm opacity-80 mt-0.5">Fakultas Teknik &bull; Berdiri {{ $selectedProdi->tahun_berdiri }}</p>
                        </div>
                    </div>
                    <button wire:click="tutupDetail" class="text-white/60 hover:text-white text-3xl leading-none shrink-0">&times;</button>
                </div>

                <div class="relative mt-5 inline-flex items-center gap-2 bg-white/20 backdrop-blur px-4 py-1.5 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    <span class="text-[11px] font-black uppercase tracking-widest">Akreditasi {{ $selectedProdi->akreditasi }}</span>
                </div>
            </div>

            <div class="p-8 space-y-8">

                {{-- 💡 STATISTIK UTAMA (Dinamis) --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @php
                        $stats = [
                            ['label' => 'Total Mahasiswa Terverifikasi',   'value' => number_format($selectedProdi->total_mahasiswa), 'icon' => '👥'],
                            ['label' => 'Dosen Pengajar',    'value' => $selectedProdi->jumlah_dosen,                    'icon' => '👨‍🏫'],
                            ['label' => 'Total Alumni',       'value' => number_format($selectedProdi->total_alumni),    'icon' => '🎓'],
                            ['label' => 'Lulusan Tahun Ini', 'value' => $selectedProdi->jumlah_lulusan_tahun_ini,        'icon' => '📜'],
                        ];
                    @endphp
                    @foreach ($stats as $stat)
                    <div class="bg-gray-50 rounded-2xl p-4 text-center">
                        <div class="text-2xl mb-1">{{ $stat['icon'] }}</div>
                        <p class="text-xl font-black text-gray-900">{{ $stat['value'] }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- KUALITAS AKADEMIK --}}
                <div>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Kualitas Akademik</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-gray-100 rounded-2xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold text-gray-500">Rata-rata IPK Lulusan</span>
                                <span class="font-black text-lg text-gray-900">{{ number_format($selectedProdi->rata_rata_ipk, 2) }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="width: {{ ($selectedProdi->rata_rata_ipk / 4) * 100 }}%; background-color: {{ $selectedProdi->warna }}">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">dari skala 4.00</p>
                        </div>
                        <div class="border border-gray-100 rounded-2xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold text-gray-500">Rata-rata Masa Studi</span>
                                <span class="font-black text-lg text-gray-900">{{ number_format($selectedProdi->rata_rata_masa_studi, 1) }} sem</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="width: {{ min(($selectedProdi->rata_rata_masa_studi / 14) * 100, 100) }}%; background-color: {{ $selectedProdi->warna }}">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">≈ {{ round($selectedProdi->rata_rata_masa_studi / 2, 1) }} tahun masa studi</p>
                        </div>
                    </div>
                </div>

                {{-- KEPALA PROGRAM STUDI --}}
                <div>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Kepala Program Studi</h3>
                    <div class="flex items-center gap-4 bg-gray-50 rounded-2xl p-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($selectedProdi->nama_kaprodi) }}&background={{ ltrim($selectedProdi->warna, '#') }}&color=fff&size=80&bold=true"
                             class="w-14 h-14 rounded-2xl shadow-sm shrink-0">
                        <div>
                            <p class="font-black text-gray-900 text-sm">{{ $selectedProdi->nama_kaprodi }}</p>
                            @if ($selectedProdi->nip_kaprodi)
                                <p class="text-[11px] text-gray-500 font-medium">NIDN: {{ $selectedProdi->nip_kaprodi }}</p>
                            @endif
                            <p class="text-[11px] text-gray-400">Kaprodi {{ $selectedProdi->nama }}</p>
                        </div>
                    </div>
                </div>

                {{-- DESKRIPSI --}}
                @if ($selectedProdi->deskripsi)
                <div>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Tentang Program Studi</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $selectedProdi->deskripsi }}</p>
                </div>
                @endif

                {{-- VISI & MISI --}}
                @if ($selectedProdi->visi || $selectedProdi->misi)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($selectedProdi->visi)
                    <div class="rounded-2xl p-5 border-l-4" style="background-color: {{ $selectedProdi->warna }}0d; border-color: {{ $selectedProdi->warna }}">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-2" style="color: {{ $selectedProdi->warna }}">🎯 Visi</p>
                        <p class="text-[12px] text-gray-600 leading-relaxed italic">{{ $selectedProdi->visi }}</p>
                    </div>
                    @endif
                    @if ($selectedProdi->misi)
                    <div class="rounded-2xl p-5 bg-gray-50">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">🚀 Misi</p>
                        <p class="text-[12px] text-gray-600 leading-relaxed">{{ $selectedProdi->misi }}</p>
                    </div>
                    @endif
                </div>
                @endif

                {{-- INFORMASI KONTAK --}}
                @if ($selectedProdi->email || $selectedProdi->telepon || $selectedProdi->website || $selectedProdi->no_sk_akreditasi)
                <div>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Informasi Kontak & Administrasi</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @if ($selectedProdi->email)
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">✉️</div>
                            <span class="font-medium break-all">{{ $selectedProdi->email }}</span>
                        </div>
                        @endif
                        @if ($selectedProdi->telepon)
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">📞</div>
                            <span class="font-medium">{{ $selectedProdi->telepon }}</span>
                        </div>
                        @endif
                        @if ($selectedProdi->website)
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">🌐</div>
                            <a href="{{ $selectedProdi->website }}" target="_blank" class="font-medium text-blue-600 hover:underline break-all">{{ $selectedProdi->website }}</a>
                        </div>
                        @endif
                        @if ($selectedProdi->no_sk_akreditasi)
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">📋</div>
                            <span class="font-medium text-[11px]">SK: {{ $selectedProdi->no_sk_akreditasi }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- TOMBOL AKSI --}}
                <div class="flex gap-3 pt-2 border-t border-gray-50">
                    @php
                        $user = auth()->user();
                        $canEdit = false;

                        if ($user) {
                            if ($user->role === 'dekan') {
                                // Dekan bisa mengedit semua prodi
                                $canEdit = true;
                            } elseif ($user->role === 'kaprodi') {
                                // Kaprodi HANYA bisa mengedit jika ID prodinya cocok dengan prodi yang sedang dibuka
                                // 💡 Catatan: Sesuaikan 'prodi_id' dengan nama kolom relasi prodi di tabel users kamu
                                $canEdit = ($user->prodi_id === $selectedProdi->id);
                            }
                        }
                    @endphp

                    {{-- Tombol Edit hanya dirender jika $canEdit bernilai true --}}
                    @if ($canEdit)
                        <button wire:click="openEditForm({{ $selectedProdi->id }}); tutupDetail()"
                            class="flex items-center gap-2 px-5 py-2.5 border-2 rounded-2xl font-black text-[11px] uppercase tracking-widest transition"
                            style="border-color: {{ $selectedProdi->warna }}; color: {{ $selectedProdi->warna }};">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Edit Data
                        </button>
                    @endif

                    <button wire:click="tutupDetail"
                        class="flex-1 px-5 py-2.5 bg-gray-100 text-gray-600 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-200 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════
         MODAL FORM TAMBAH / EDIT
    ══════════════════════════════════════════════ --}}
    @if ($showFormModal)
    <div class="fixed inset-0 z-[9999] flex items-start justify-center p-4 pt-8 bg-black/50 backdrop-blur-sm overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden mb-10">

            <div class="p-6 border-b border-gray-100 flex justify-between items-center"
                 style="background-color: {{ $f_warna ?? '#6366f1' }}1a">
                <div>
                    <h3 class="font-black text-gray-900 uppercase italic text-lg">
                        {{ $isEditing ? 'Edit Program Studi' : 'Tambah Program Studi Baru' }}
                    </h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">Fakultas Teknik &bull; Universitas Pahlawan Tuanku Tambusai</p>
                </div>
                <button wire:click="$set('showFormModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>

            <form wire:submit.prevent="saveProdi" class="p-8 space-y-6 max-h-[80vh] overflow-y-auto">

                {{-- Identitas Prodi --}}
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Identitas Program Studi</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Prodi <span class="text-red-400">*</span></label>
                            <input wire:model="f_nama" type="text" placeholder="Contoh: Teknik Informatika"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 @error('f_nama') border-red-400 @enderror">
                            @error('f_nama') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kode Prodi <span class="text-red-400">*</span></label>
                            <input wire:model="f_kode" type="text" placeholder="Contoh: TI"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 @error('f_kode') border-red-400 @enderror">
                            @error('f_kode') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jenjang</label>
                            <select wire:model="f_jenjang" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold">
                                <option value="S1">S1 (Sarjana)</option>
                                <option value="D3">D3 (Diploma)</option>
                                <option value="S2">S2 (Magister)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tahun Berdiri <span class="text-red-400">*</span></label>
                            <input wire:model="f_tahun_berdiri" type="number" min="1900" max="{{ now()->year }}" placeholder="Contoh: 2001"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm @error('f_tahun_berdiri') border-red-400 @enderror">
                            @error('f_tahun_berdiri') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Akreditasi</label>
                            <select wire:model="f_akreditasi" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold">
                                <option value="Unggul">Unggul</option>
                                <option value="Baik Sekali">Baik Sekali</option>
                                <option value="Baik">Baik</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Icon (Emoji)</label>
                            <input wire:model="f_icon" type="text" placeholder="Contoh: 💻"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Warna Tema</label>
                            <div class="flex items-center gap-2">
                                <input wire:model.live="f_warna" type="color" value="{{ $f_warna }}"
                                    class="w-10 h-10 rounded-lg border-0 cursor-pointer bg-transparent p-0.5">
                                <input wire:model.live="f_warna" type="text" value="{{ $f_warna }}"
                                    class="flex-1 bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kepala Prodi --}}
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Kepala Program Studi</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Kaprodi <span class="text-red-400">*</span></label>
                            <input wire:model="f_nama_kaprodi" type="text" placeholder="Contoh: Dr. Ahmad, M.Kom"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm @error('f_nama_kaprodi') border-red-400 @enderror">
                            @error('f_nama_kaprodi') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">NIP Kaprodi</label>
                            <input wire:model="f_nip_kaprodi" type="text" placeholder="18 digit NIP"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Statistik (Input Mahasiswa & Alumni dihapus) --}}
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Statistik Lainnya</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jml. Dosen</label>
                            <input wire:model="f_jumlah_dosen" type="number" min="0" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Lulus Tahun Ini</label>
                            <input wire:model="f_jumlah_lulusan_tahun_ini" type="number" min="0" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Rata-rata IPK</label>
                            <input wire:model="f_rata_rata_ipk" type="number" step="0.01" min="0" max="4" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Masa Studi (Sem)</label>
                            <input wire:model="f_rata_rata_masa_studi" type="number" step="0.1" min="0" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Deskripsi, Visi, Misi --}}
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Deskripsi & Visi Misi</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi Singkat</label>
                            <textarea wire:model="f_deskripsi" rows="2" placeholder="Gambaran umum program studi..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Visi</label>
                            <textarea wire:model="f_visi" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Misi</label>
                            <textarea wire:model="f_misi" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Kontak --}}
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Kontak & Administrasi</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email</label>
                            <input wire:model="f_email" type="email" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Telepon</label>
                            <input wire:model="f_telepon" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Website</label>
                            <input wire:model="f_website" type="url" placeholder="https://" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">No. SK Akreditasi</label>
                            <input wire:model="f_no_sk_akreditasi" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="flex gap-4 pt-2 border-t border-gray-100">
                    <button type="button" wire:click="$set('showFormModal', false)"
                        class="flex-1 px-6 py-3 bg-gray-100 text-gray-500 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 px-6 py-3 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-xl transition flex items-center justify-center gap-2"
                        style="background-color: {{ $f_warna ?? '#6366f1' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Prodi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
    window.addEventListener('swal:success', event => {
        Swal.fire({
            title: event.detail.title,
            text: event.detail.text,
            icon: 'success',
            confirmButtonColor: '#111827',
            timer: 3000,
            timerProgressBar: true,
        });
    });
</script>