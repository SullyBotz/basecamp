@section('title', 'User')
<?php
use App\Models\User;
use App\Models\Profile;
use App\Models\ProgramStudi;
use App\Models\Notification; 
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; 

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';

    // State untuk Import Excel
    public $showImportModal = false;
    public $import_file;

    // State untuk Modal Edit
    public $showEditModal = false;
    public $selectedUserId;
    public $name, $email, $nim, $whatsapp, $role, $prodi_id;
    public $angkatan, $thesis_title, $pembimbing_1, $pembimbing_2; 

    // State untuk Modal Tambah Akun
    public $showAddModal = false;
    public $add_name = '';
    public $add_email = '';
    public $add_nim = '';
    public $add_whatsapp = ''; 
    public $add_role = 'mahasiswa';
    public $add_prodi_id = '';
    public $add_password = '';
    public $add_password_confirmation = '';
    public $add_angkatan = '';
    public $add_thesis_title = '';
    public $add_pembimbing_1 = ''; 
    public $add_pembimbing_2 = ''; 

    // State untuk View Profile (Khusus Mahasiswa/Alumni)
    public $showViewModal = false;
    public $viewUser = null;

    public function with()
    {
        $authRole = auth()->user()->role;

        // Mengambil data notifikasi belum dibaca dari tabel kustom untuk Dekan & Kaprodi
        $unreadNotifs = [];
        $unreadCount = 0;

        if (in_array($authRole, ['dekan', 'kaprodi'])) {
            $unreadNotifs = Notification::where('user_id', auth()->id())
                                ->where('is_read', 0)
                                ->latest()
                                ->get();
            $unreadCount = $unreadNotifs->count();
        }

        return [
            'users' => User::with(['profile', 'prodi'])
                ->when($authRole === 'dekan', function ($query) {
                    $query->where('role', '!=', 'dekan');
                })
                ->when($authRole === 'kaprodi', function ($query) {
                    $query->whereNotIn('role', ['dekan', 'kaprodi']);
                })
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nim', 'like', '%' . $this->search . '%');
                })
                ->orderBy('is_verified', 'asc') 
                ->latest()
                ->paginate(10),
            'programStudis' => ProgramStudi::where('is_active', true)->orderBy('nama')->get(),
            'unreadNotifs'  => $unreadNotifs,
            'unreadCount'   => $unreadCount,
        ];
    }

    // =====================================================
    // FITUR MANAJEMEN NOTIFIKASI KUSTOM
    // =====================================================
    public function markNotificationAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification && $notification->user_id === auth()->id()) {
            $notification->is_read = 1;
            $notification->save();
        }
    }

    public function markAllNotificationsAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
    }

    // =====================================================
    // FITUR VERIFIKASI AKUN BARU & NOTIFIKASI WHATSAPP
    // =====================================================
    public function approveUser($id)
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $user = User::find($id);
        if ($user) {
            $user->is_verified = true;
            $user->save();

            if (!empty($user->whatsapp)) {
                $pesanWA = "Halo *{$user->name}*! 🎓\n\n";
                $pesanWA .= "Pendaftaran akun Anda di *Portal Digital Fakultas Teknik Universitas Pahlawan Tuanku Tambusai* telah berhasil diverifikasi oleh pihak Fakultas/Prodi.\n\n";
                $pesanWA .= "Silakan login menggunakan email dan password yang telah Anda daftarkan.\n\n";
                $pesanWA .= "Demi keamanan, mohon untuk tidak membagikan informasi akun anda kepada pihak lain. \n\n";
                $pesanWA .= "Terima kasih.";

                try {
                    $response = Http::timeout(5)
                        ->withoutVerifying()
                        ->withHeaders([
                            'Authorization' => 's5JCWdsj7MRWVjFF6uQT' // Token Fonnte
                        ])->post('https://api.fonnte.com/send', [
                            'target' => $user->whatsapp,
                            'message' => $pesanWA,
                            'countryCode' => '62',
                        ]);

                    \Illuminate\Support\Facades\Log::info('Respon Fonnte: ' . $response->body());
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim WA: ' . $e->getMessage());
                }
            }

            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Pendaftaran disetujui & Notifikasi WA terkirim.');
        }
    }

    public function rejectUser($id)
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $user = User::find($id);
        if ($user) {
            $user->delete(); 
            $this->dispatch('swal:success', title: 'Ditolak!', text: 'Pendaftaran akun telah ditolak dan dihapus.');
        }
    }

    // =====================================================
    // FITUR LIHAT PROFIL (KHUSUS MAHASISWA & ALUMNI)
    // =====================================================
    public function viewProfile($id)
    {
        $this->viewUser = User::with(['profile', 'prodi'])->find($id);
        if ($this->viewUser) {
            $this->showViewModal = true;
        }
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewUser = null;
    }

    // =====================================================
    // FITUR IMPORT EXCEL
    // =====================================================
    public function openImportModal()
    {
        $this->reset('import_file');
        $this->showImportModal = true;
    }

    public function importData()
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $this->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120'
        ], [
            'import_file.required' => 'File Excel wajib diunggah.',
            'import_file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv',
            'import_file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->import_file->getRealPath());
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray();
            
            // Hapus baris pertama jika itu adalah header
            array_shift($rows);

            $importedCount = 0;

            foreach ($rows as $row) {
                // Skip jika nama atau email kosong (karena ini wajib)
                if (empty($row[0]) || empty($row[1])) continue;

                // Format Kolom: name, email, pembimbing_1, pembimbing_2, nim, password, whatsapp, role, prodi_id, angkatan, judul_skripsi
                $name         = $row[0];
                $email        = $row[1];
                $pembimbing_1 = $row[2] ?? null;
                $pembimbing_2 = $row[3] ?? null;
                $nim          = $row[4] ?? null;
                $password     = !empty($row[5]) ? $row[5] : 'password123';
                $whatsapp     = $row[6] ?? null;
                $role         = isset($row[7]) ? strtolower(trim($row[7])) : 'mahasiswa';
                $prodi_id     = $row[8] ?? null; // 1 = TI, 2 = Sipil, 3 = Industri
                $angkatan     = $row[9] ?? null;
                $thesis_title = $row[10] ?? null;

                // Cek apakah email atau nim sudah ada di database
                if (User::where('email', $email)->exists() || ($nim && User::where('nim', $nim)->exists())) {
                    continue; 
                }

                // Buat User
                $user = User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'nim'               => $nim,
                    'whatsapp'          => $whatsapp,
                    'role'              => in_array($role, ['mahasiswa', 'alumni']) ? $role : 'mahasiswa',
                    'prodi_id'          => in_array($role, ['mahasiswa', 'alumni']) ? $prodi_id : null,
                    'password'          => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_verified'       => true, 
                ]);

                // Buat Profil jika role mahasiswa atau alumni
                if (in_array($role, ['mahasiswa', 'alumni'])) {
                    $user->profile()->create([
                        'angkatan'     => $angkatan ?: null,
                        'thesis_title' => $role === 'alumni' ? ($thesis_title ?: null) : null,
                        'pembimbing_1' => $role === 'alumni' ? ($pembimbing_1 ?: null) : null,
                        'pembimbing_2' => $role === 'alumni' ? ($pembimbing_2 ?: null) : null,
                    ]);
                }

                $importedCount++;
            }

            $this->showImportModal = false;
            $this->import_file = null;
            $this->dispatch('swal:success', title: 'Berhasil!', text: "$importedCount akun berhasil diimport.");

        } catch (\Exception $e) {
            $this->addError('import_file', 'Terjadi kesalahan saat membaca file: ' . $e->getMessage());
        }
    }

    // =====================================================
    // FITUR TAMBAH AKUN BARU
    // =====================================================
    public function openAddModal()
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $this->reset([
            'add_name', 'add_email', 'add_nim', 'add_whatsapp', 'add_role', 'add_prodi_id',
            'add_password', 'add_password_confirmation',
            'add_angkatan', 'add_thesis_title', 'add_pembimbing_1', 'add_pembimbing_2',
        ]);
        $this->add_role = 'mahasiswa';
        $this->showAddModal = true;
    }

    public function storeUser()
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $allowedRoles = auth()->user()->role === 'dekan'
            ? 'mahasiswa,alumni,kaprodi'
            : 'mahasiswa,alumni';

        $rules = [
            'add_name'          => 'required|string|max:255',
            'add_email'         => 'required|email|unique:users,email',
            'add_nim'           => $this->add_role === 'kaprodi' ? 'required|string|max:20|unique:users,nim' : 'nullable|string|max:20|unique:users,nim',
            'add_whatsapp'      => 'nullable|string|max:20|unique:users,whatsapp', 
            'add_role'          => 'required|in:' . $allowedRoles,
            'add_prodi_id'      => in_array($this->add_role, ['mahasiswa', 'alumni']) ? 'required|exists:program_studis,id' : 'nullable',
            'add_password'      => 'required|min:8|confirmed',
            'add_angkatan'      => $this->add_role !== 'kaprodi' ? 'nullable|numeric|digits:4' : 'nullable',
        ];

        if ($this->add_role === 'alumni') {
            $rules['add_thesis_title'] = 'required|string|max:500';
            $rules['add_pembimbing_1'] = 'required|string|max:255';
            $rules['add_pembimbing_2'] = 'required|string|max:255';
        }

        $this->validate($rules);

        $user = User::create([
            'name'              => $this->add_name,
            'email'             => $this->add_email,
            'nim'               => $this->add_nim ?: null,
            'whatsapp'          => $this->add_whatsapp ?: null,
            'role'              => $this->add_role,
            'prodi_id'          => in_array($this->add_role, ['mahasiswa', 'alumni']) ? $this->add_prodi_id : null,
            'password'          => Hash::make($this->add_password),
            'email_verified_at' => now(),
            'is_verified'       => true,
        ]);

        if (in_array($this->add_role, ['mahasiswa', 'alumni'])) {
            $profileData = [
                'angkatan' => $this->add_angkatan ?: null,
            ];

            if ($this->add_role === 'alumni') {
                $profileData['thesis_title'] = $this->add_thesis_title;
                $profileData['pembimbing_1'] = $this->add_pembimbing_1;
                $profileData['pembimbing_2'] = $this->add_pembimbing_2;
            }

            $user->profile()->create($profileData);
        }

        $this->showAddModal = false;
        $this->dispatch('swal:success', title: 'Berhasil!', text: 'Akun baru telah dibuat.');
    }

    // =====================================================
    // FITUR EDIT & DELETE
    // =====================================================
    public function editUser($id)
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $user = User::with('profile')->findOrFail($id);

        if (auth()->user()->role === 'kaprodi' && in_array($user->role, ['dekan', 'kaprodi'])) {
            return;
        }

        $this->selectedUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nim = $user->nim;
        $this->whatsapp = $user->whatsapp;
        $this->role = $user->role;
        $this->prodi_id = $user->prodi_id;
        
        $this->angkatan = $user->profile?->angkatan;
        $this->thesis_title = $user->profile?->thesis_title;
        $this->pembimbing_1 = $user->profile?->pembimbing_1;
        $this->pembimbing_2 = $user->profile?->pembimbing_2;

        $this->showEditModal = true;
    }

    public function updateUser()
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $allowedRoles = auth()->user()->role === 'dekan' || auth()->user()->role === 'kaprodi'
            ? 'mahasiswa,alumni,kaprodi'
            : 'mahasiswa,alumni';

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $this->selectedUserId,
            'nim'       => $this->role === 'kaprodi' ? 'required|string|max:20|unique:users,nim,' . $this->selectedUserId : 'nullable|string|max:20|unique:users,nim,' . $this->selectedUserId,
            'whatsapp'  => 'nullable|string|max:20|unique:users,whatsapp,' . $this->selectedUserId,
            'role'      => 'required|in:' . $allowedRoles,
            'prodi_id'  => in_array($this->role, ['mahasiswa', 'alumni']) ? 'required|exists:program_studis,id' : 'nullable',
            'angkatan'  => $this->role !== 'kaprodi' ? 'nullable|numeric|digits:4' : 'nullable',
        ];

        if ($this->role === 'alumni') {
            $rules['thesis_title'] = 'required|string|max:500';
            $rules['pembimbing_1'] = 'required|string|max:255';
            $rules['pembimbing_2'] = 'required|string|max:255';
        }

        $this->validate($rules);

        $user = User::find($this->selectedUserId);
        $user->update([
            'name'     => $this->name,
            'email'    => $this->email,
            'nim'      => $this->nim,
            'whatsapp' => $this->whatsapp,
            'role'     => $this->role,
            'prodi_id' => in_array($this->role, ['mahasiswa', 'alumni']) ? $this->prodi_id : null,
        ]);

        if (in_array($this->role, ['mahasiswa', 'alumni'])) {
            $profileData = [
                'angkatan' => $this->angkatan,
            ];

            if ($this->role === 'alumni') {
                $profileData['thesis_title'] = $this->thesis_title;
                $profileData['pembimbing_1'] = $this->pembimbing_1;
                $profileData['pembimbing_2'] = $this->pembimbing_2;
            } else {
                $profileData['thesis_title'] = null;
                $profileData['pembimbing_1'] = null;
                $profileData['pembimbing_2'] = null;
            }

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        } else {
            $user->profile()->delete(); 
        }

        $this->showEditModal = false;
        session()->flash('message', 'Data berhasil diperbarui.');
    }

    public function deleteUser($id)
    {
        if (in_array(auth()->user()->role, ['mahasiswa', 'alumni'])) return;

        $user = User::find($id);
        if ($user) {
            if (auth()->user()->role === 'kaprodi' && in_array($user->role, ['dekan', 'kaprodi'])) {
                return;
            }

            \App\Models\Message::where('sender_id', $id)->orWhere('receiver_id', $id)->delete();
            \App\Models\Job::where('user_id', $id)->delete();
            \App\Models\Profile::where('user_id', $id)->delete();
            $user->delete();
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data telah dihapus.');
        }
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header Section --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h2 class="text-2xl font-black text-gray-900 uppercase">
            {{ in_array(auth()->user()->role, ['mahasiswa', 'alumni']) ? 'Direktori Anggota' : 'Manajemen Akun' }}
        </h2>

        <div class="flex items-center gap-3">
            <input wire:model.live="search" type="text" placeholder="Cari nama, nim, atau nidn..."
                class="rounded-2xl border-gray-200 text-sm w-full md:w-64 shadow-sm focus:ring-purple-500">

            @if (!in_array(auth()->user()->role, ['mahasiswa', 'alumni']))
                <button wire:click="openImportModal"
                    class="flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-green-700 shadow-lg shadow-green-200 transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import Excel
                </button>

                <button wire:click="openAddModal"
                    class="flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-purple-700 shadow-lg shadow-purple-200 transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Akun
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="mb-4 px-5 py-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-semibold">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabel Utama --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50/50">
                <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    <th class="px-6 py-4">Informasi Dasar</th>
                    <th class="px-6 py-4">Akademik & Instansi</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50/30 transition {{ !$user->is_verified ? 'bg-orange-50/20' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random"
                                    class="w-12 h-12 rounded-2xl shadow-sm">
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">{{ $user->name }}</p>
                                    
                                    {{-- 💡 LOGIKA PENYEMBUNYIAN EMAIL DI TABEL UTAMA --}}
                                    @if(in_array(auth()->user()->role, ['dekan', 'kaprodi']) || auth()->id() === $user->id)
                                        <p class="text-[11px] text-gray-500 font-medium">{{ $user->email }}</p>
                                    @else
                                        <p class="text-[11px] text-gray-400 italic flex items-center gap-1 mt-0.5" title="Disembunyikan demi privasi">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                            Email Disembunyikan
                                        </p>
                                    @endif

                                    <span class="inline-block mt-1 text-[9px] px-2 py-0.5 rounded-md bg-purple-50 text-purple-600 font-bold uppercase tracking-tighter">{{ $user->role }}</span>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            @if ($user->role === 'dekan')
                                <p class="text-xs font-bold text-gray-700">NIDN: {{ $user->nim ?? '-' }}</p>
                                <p class="text-[10px] text-amber-600 font-bold">Dekan Fakultas Teknik</p>
                            @elseif ($user->role === 'kaprodi')
                                <p class="text-xs font-bold text-gray-700">NIDN: {{ $user->nim ?? '-' }}</p>
                                <p class="text-[10px] text-amber-600 font-bold">Ketua Program Studi</p>
                            @else
                                <p class="text-xs font-bold text-gray-700">NIM: {{ $user->nim ?? '-' }}</p>
                                <p class="text-[11px] text-purple-600 font-semibold">{{ $user->prodi?->nama ?? 'Prodi Belum Dipilih' }}</p>

                                @if ($user->role === 'alumni')
                                    <p class="text-[10px] text-gray-500">Lulus: <span class="text-gray-900 font-bold">{{ $user->profile?->angkatan ?? '-' }}</span></p>
                                    <p class="text-[10px] text-gray-400 italic truncate w-48" title="{{ $user->profile?->thesis_title }}">
                                        "{{ $user->profile?->thesis_title ?? 'Judul skripsi belum diisi' }}"
                                    </p>
                                @else
                                    <p class="text-[10px] text-blue-500 font-bold">Mahasiswa Aktif (Angkatan {{ $user->profile?->angkatan ?? '-' }})</p>
                                @endif
                            @endif
                            
                        </td>
                        
                        <td class="px-6 py-4">
                            @if($user->is_verified)
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-full uppercase">Aktif</span>
                            @else
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 text-[10px] font-bold rounded-full uppercase animate-pulse">Menunggu Verifikasi</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                
                                {{-- 1. TAMPILAN AKSI KHUSUS ROLE MAHASISWA & ALUMNI --}}
                                @if(in_array(auth()->user()->role, ['mahasiswa', 'alumni']))
                                    <button wire:click="viewProfile({{ $user->id }})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition" title="Lihat Profil">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    
                                    {{-- Tombol Kirim Pesan Logo Chat Bubble Modern --}}
                                    <button href="{{ route('messages.index', ['user' => $user->id]) }}" wire:navigate class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition" title="Kirim Pesan">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </button>
                                
                                {{-- 2. TAMPILAN AKSI KHUSUS MANAGEMENT (DEKAN, KAPRODI) --}}
                                @else
                                    @if (!$user->is_verified)
                                        <button wire:click="approveUser({{ $user->id }})" class="p-2 text-green-500 hover:bg-green-100 rounded-lg transition" title="Setujui Akun">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button onclick="confirmReject({{ $user->id }}, '{{ $user->name }}')" class="p-2 text-red-500 hover:bg-red-100 rounded-lg transition" title="Tolak Akun">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @else
                                        <button wire:click="editUser({{ $user->id }})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition" title="Edit Data">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')" class="p-2 text-gray-400 hover:text-red-600 transition" title="Hapus Akun">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif

                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center text-gray-400 text-sm">
                            Tidak ada data yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-50">
            {{ $users->links() }}
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL LIHAT PROFIL (KHUSUS MAHASISWA & ALUMNI)               --}}
    {{-- ============================================================ --}}
    @if($showViewModal && $viewUser)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden shadow-blue-500/10">
                <div class="bg-gradient-to-r from-blue-600 to-blue-400 p-6 text-center relative">
                    <button wire:click="closeViewModal" class="absolute top-4 right-4 text-white/70 hover:text-white text-xl">&times;</button>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($viewUser->name) }}&background=ffffff&color=2563eb" 
                         class="w-20 h-20 rounded-full mx-auto border-4 border-white/30 shadow-lg">
                    <h3 class="text-white font-black text-lg mt-3">{{ $viewUser->name }}</h3>
                    <p class="text-blue-100 text-xs">{{ strtoupper($viewUser->role) }}</p>
                </div>
                <div class="p-6 space-y-4 bg-gray-50/50 max-h-[60vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Email</p>
                            
                            {{-- 💡 LOGIKA PENYEMBUNYIAN EMAIL DI MODAL PROFIL --}}
                            @if(in_array(auth()->user()->role, ['dekan', 'kaprodi']) || auth()->id() === $viewUser->id)
                                <p class="text-sm text-gray-800 font-medium truncate" title="{{ $viewUser->email }}">{{ $viewUser->email }}</p>
                            @else
                                <p class="text-sm text-gray-400 italic">Disembunyikan</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">WhatsApp</p>

                            {{-- 💡 LOGIKA PENYEMBUNYIAN WHATSAPP DI MODAL PROFIL --}}
                            @if(in_array(auth()->user()->role, ['dekan', 'kaprodi']) || auth()->id() === $viewUser->id)
                                <p class="text-sm text-gray-800 font-medium">{{ $viewUser->whatsapp ?? '-' }}</p>
                            @else
                                <p class="text-sm text-gray-400 italic">Disembunyikan</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Program Studi</p>
                        <p class="text-sm text-gray-800 font-medium">{{ $viewUser->prodi?->nama ?? '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">NIM</p>
                            <p class="text-sm text-gray-800 font-medium">{{ $viewUser->nim ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Angkatan</p>
                            <p class="text-sm text-gray-800 font-medium">{{ $viewUser->profile?->angkatan ?? '-' }}</p>
                        </div>
                    </div>

                    @if($viewUser->role === 'alumni')
                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Informasi Skripsi</p>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-[10px] text-gray-400 font-medium">Judul</p>
                                    <p class="text-xs text-gray-700 italic bg-white p-2.5 rounded-lg border border-gray-100">
                                        "{{ $viewUser->profile?->thesis_title ?? 'Belum ada data' }}"
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-medium">Pembimbing 1</p>
                                        <p class="text-xs text-gray-800 font-semibold">{{ $viewUser->profile?->pembimbing_1 ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-medium">Pembimbing 2</p>
                                        <p class="text-xs text-gray-800 font-semibold">{{ $viewUser->profile?->pembimbing_2 ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL IMPORT EXCEL (Disembunyikan Jika Mahasiswa/Alumni)     --}}
    {{-- ============================================================ --}}
    @if ($showImportModal && !in_array(auth()->user()->role, ['mahasiswa', 'alumni']))
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden shadow-green-500/10">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-green-600">
                    <div>
                        <h3 class="font-black text-white uppercase italic text-lg">Import Data</h3>
                        <p class="text-green-200 text-[11px] mt-0.5">Mahasiswa & Alumni</p>
                    </div>
                    <button wire:click="$set('showImportModal', false)" class="text-green-200 hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="importData" class="p-8 space-y-5">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Upload File Excel (.xlsx, .csv)</label>
                        <input type="file" wire:model="import_file" accept=".xlsx,.xls,.csv"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        <div wire:loading wire:target="import_file" class="mt-2 text-xs text-green-600 font-semibold">Mengunggah file...</div>
                        @error('import_file') <p class="text-red-500 text-[10px] mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 h-48 overflow-y-auto">
                        <p class="text-[10px] font-bold text-gray-500 uppercase mb-2">Struktur Kolom Excel (Urutan wajib):</p>
                        <ol class="text-[11px] text-gray-500 list-decimal list-inside space-y-1">
                            <li>Nama Lengkap <span class="text-red-500">*</span></li>
                            <li>Email <span class="text-red-500">*</span></li>
                            <li>Pembimbing 1 <span class="italic text-gray-400">(Opsional)</span></li>
                            <li>Pembimbing 2 <span class="italic text-gray-400">(Opsional)</span></li>
                            <li>NIM <span class="text-red-500">*</span></li>
                            <li>Password <span class="italic text-gray-400">(Ops. Default: password123)</span></li>
                            <li>WhatsApp <span class="italic text-gray-400">(Opsional)</span></li>
                            <li>Role <span class="font-bold">(mahasiswa / alumni)</span></li>
                            <li>ID Prodi <span class="font-bold">(1: TI, 2: Sipil, 3: Industri)</span></li>
                            <li>Angkatan <span class="italic text-gray-400">(Opsional)</span></li>
                            <li>Judul Skripsi <span class="italic text-gray-400">(Opsional)</span></li>
                        </ol>
                    </div>

                    <div class="pt-2 flex gap-4">
                        <button type="button" wire:click="$set('showImportModal', false)" class="flex-1 px-6 py-3 bg-gray-100 text-gray-500 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-green-700 shadow-xl shadow-green-200 transition flex items-center justify-center gap-2">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL TAMBAH AKUN BARU                                       --}}
    {{-- ============================================================ --}}
    @if ($showAddModal && !in_array(auth()->user()->role, ['mahasiswa', 'alumni']))
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm py-10">
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden shadow-purple-500/10 flex flex-col max-h-full">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-purple-600 shrink-0">
                    <div>
                        <h3 class="font-black text-white uppercase italic text-lg">Tambah Akun Baru</h3>
                        <p class="text-purple-200 text-[11px] mt-0.5">Manajemen User Tingkat Admin</p>
                    </div>
                    <button wire:click="$set('showAddModal', false)" class="text-purple-200 hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="storeUser" class="p-8 space-y-5 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Lengkap <span class="text-red-400">*</span></label>
                            <input wire:model="add_name" type="text" placeholder="Contoh: Budi Santoso" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_name') border-red-400 @enderror">
                            @error('add_name') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">{{ $add_role === 'kaprodi' ? 'NIDN' : 'NIM' }} <span class="text-red-400">*</span></label>
                            <input wire:model="add_nim" type="text" placeholder="{{ $add_role === 'kaprodi' ? 'Masukkan NIDN' : 'Masukkan NIM' }}" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_nim') border-red-400 @enderror">
                            @error('add_nim') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Alamat Email <span class="text-red-400">*</span></label>
                            <input wire:model="add_email" type="email" placeholder="contoh@email.com" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_email') border-red-400 @enderror">
                            @error('add_email') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">No. WhatsApp</label>
                            <input wire:model="add_whatsapp" type="text" placeholder="Contoh: 08123456789" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_whatsapp') border-red-400 @enderror">
                            @error('add_whatsapp') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="{{ $add_role === 'kaprodi' ? 'col-span-2' : '' }}">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Role Akun <span class="text-red-400">*</span></label>
                            <select wire:model.live="add_role" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-purple-600 focus:ring-purple-500">
                                <option value="mahasiswa">MAHASISWA</option>
                                <option value="alumni">ALUMNI</option>
                                @if (auth()->user()->role === 'dekan')
                                    <option value="kaprodi">KAPRODI</option>
                                @endif
                            </select>
                        </div>
                        @if ($add_role !== 'kaprodi')
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Angkatan / Tahun Lulus</label>
                                <input wire:model="add_angkatan" type="text" maxlength="4" placeholder="Contoh: 2021" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_angkatan') border-red-400 @enderror">
                                @error('add_angkatan') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    @if (in_array($add_role, ['mahasiswa', 'alumni']))
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Program Studi <span class="text-red-400">*</span></label>
                            <select wire:model="add_prodi_id" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-purple-600 focus:ring-purple-500 @error('add_prodi_id') border-red-400 @enderror">
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach ($programStudis as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->jenjang }} - {{ $prodi->nama }}</option>
                                @endforeach
                            </select>
                            @error('add_prodi_id') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($add_role === 'alumni')
                        <div class="p-4 bg-purple-50/50 border border-purple-100 rounded-2xl space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-purple-600 mb-1">Data Skripsi</p>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Judul Skripsi</label>
                                <textarea wire:model="add_thesis_title" rows="2" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm placeholder:italic focus:ring-purple-500" placeholder="Masukkan judul penelitian..."></textarea>
                                @error('add_thesis_title') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pembimbing 1</label>
                                    <input wire:model="add_pembimbing_1" type="text" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500">
                                    @error('add_pembimbing_1') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pembimbing 2</label>
                                    <input wire:model="add_pembimbing_2" type="text" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500">
                                    @error('add_pembimbing_2') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-gray-100 pt-1">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-3">Keamanan Akun</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Password <span class="text-red-400">*</span></label>
                            <input wire:model="add_password" type="password" placeholder="Min. 8 karakter" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500 @error('add_password') border-red-400 @enderror">
                            @error('add_password') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Konfirmasi Password <span class="text-red-400">*</span></label>
                            <input wire:model="add_password_confirmation" type="password" placeholder="Ulangi password" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-purple-500">
                        </div>
                    </div>

                    <div class="pt-2 flex gap-4 shrink-0">
                        <button type="button" wire:click="$set('showAddModal', false)" class="flex-1 px-6 py-3 bg-gray-100 text-gray-500 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-purple-700 shadow-xl shadow-purple-200 transition flex items-center justify-center gap-2">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL EDIT AKUN ANGGOTA                                      --}}
    {{-- ============================================================ --}}
    @if ($showEditModal && !in_array(auth()->user()->role, ['mahasiswa', 'alumni']))
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm py-10">
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden shadow-purple-500/10 flex flex-col max-h-full">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 shrink-0">
                    <h3 class="font-black text-gray-900 uppercase italic">Update Profil Anggota</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form wire:submit="updateUser" class="p-8 space-y-5 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Lengkap</label>
                            <input wire:model="name" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">{{ $role === 'kaprodi' ? 'NIDN' : 'NIM' }}</label>
                            <input wire:model="nim" type="text" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email</label>
                            <input wire:model="email" type="email" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">WhatsApp</label>
                            <input wire:model="whatsapp" type="text" placeholder="08123..." class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="{{ $role === 'kaprodi' ? 'col-span-2' : '' }}">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Role Akun</label>
                            <select wire:model.live="role" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-purple-600">
                                <option value="mahasiswa">MAHASISWA</option>
                                <option value="alumni">ALUMNI</option>
                                @if (auth()->user()->role === 'dekan')
                                    <option value="kaprodi">KAPRODI</option>
                                @endif
                            </select>
                        </div>
                        @if ($role !== 'kaprodi')
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Angkatan / Tahun Lulus</label>
                                <input wire:model="angkatan" type="text" maxlength="4" placeholder="Contoh: 2020" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm">
                            </div>
                        @endif
                    </div>

                    @if (in_array($role, ['mahasiswa', 'alumni']))
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Program Studi <span class="text-red-400">*</span></label>
                            <select wire:model="prodi_id" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-purple-600 focus:ring-purple-500">
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach ($programStudis as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->jenjang }} - {{ $prodi->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($role === 'alumni')
                        <div class="p-4 bg-gray-50/80 border border-gray-100 rounded-2xl space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Data Skripsi</p>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Judul Skripsi</label>
                                <textarea wire:model="thesis_title" rows="2" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm placeholder:italic" placeholder="Masukkan judul penelitian..."></textarea>
                                @error('thesis_title') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pembimbing 1</label>
                                    <input wire:model="pembimbing_1" type="text" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm">
                                    @error('pembimbing_1') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pembimbing 2</label>
                                    <input wire:model="pembimbing_2" type="text" class="w-full bg-white border-gray-200 rounded-xl px-4 py-2 text-sm">
                                    @error('pembimbing_2') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 flex gap-4 shrink-0">
                        <button type="button" wire:click="$set('showEditModal', false)" class="flex-1 px-6 py-3 bg-gray-100 text-gray-500 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-purple-700 shadow-xl shadow-purple-200 transition">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

<script>
    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Hapus Permanen?',
            text: "Data user bernama " + name + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteUser', id);
            }
        })
    }

    function confirmReject(id, name) {
        Swal.fire({
            title: 'Tolak Pendaftaran?',
            text: "Pendaftaran akun atas nama " + name + " akan ditolak dan dihapus.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Tolak!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('rejectUser', id);
            }
        })
    }

    window.addEventListener('swal:success', event => {
        Swal.fire({
            title: event.detail.title,
            text: event.detail.text,
            icon: event.detail.icon || 'success',
            confirmButtonColor: '#7c3aed',
            timer: 3000
        });
    });
</script>