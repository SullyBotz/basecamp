@section('title', 'Profil Pengguna')

<?php
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public User $user;

    public function with()
    {
        $roleLabels = [
            'dekan'      => 'Dekan Fakultas Teknik',
            'kaprodi'    => 'Kepala Program Studi',
            'mahasiswa'  => 'Mahasiswa Aktif',
            'alumni'     => 'Alumni Fakultas Teknik',
        ];

        return [
            'postsCount' => $this->user->posts()->count(),
            'roleLabel'  => $roleLabels[$this->user->role] ?? 'Civitas Akademika',
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- Cover Banner --}}
        <div class="h-32 bg-gradient-to-r from-blue-500 to-cyan-400"></div>
        
        <div class="px-8 pb-8">
            <div class="relative flex justify-between items-end -mt-12 mb-6">
                {{-- Foto Profil --}}
                <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                    class="w-32 h-32 rounded-3xl border-4 border-white object-cover shadow-sm"
                    alt="{{ $user->name }}">

                {{-- Tombol Kirim Pesan --}}
                @if (auth()->id() !== $user->id)
                    <a href="{{ route('messages.index', ['user' => $user->id]) }}" wire:navigate
                        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition">
                        Kirim Pesan
                    </a>
                @endif
            </div>

            {{-- Informasi Utama --}}
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black text-gray-900">{{ $user->name }}</h2>
                    {{-- Badge Role Dinamis --}}
                    <span class="text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full 
                        @if($user->role === 'dekan') bg-purple-100 text-purple-700 
                        @elseif($user->role === 'kaprodi') bg-indigo-100 text-indigo-700 
                        @elseif($user->role === 'mahasiswa') bg-blue-100 text-blue-700 
                        @else bg-emerald-100 text-emerald-750 @endif">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>

                {{-- 💡 NAMA PROGRAM STUDI HANYA MUNCUL UNTUK MAHASISWA & ALUMNI --}}
                @if($user->prodi && in_array($user->role, ['mahasiswa', 'alumni']))
                    <p class="text-xs font-bold text-purple-600 mt-1 uppercase tracking-wider">
                        🎓 Prodi: {{ $user->prodi->nama }} ({{ $user->prodi->jenjang }})
                    </p>
                @endif

                <p class="text-gray-500 font-medium mt-1">
                    @if(in_array($user->role, ['dekan', 'kaprodi']))
                        {{ $roleLabel }} &bull; Universitas Pahlawan
                    @else
                        {{ $user->profile?->current_job ?? ($user->role === 'mahasiswa' ? 'Mahasiswa Fakultas Teknik' : 'Alumni') }} 
                        @if($user->profile?->company) @ {{ $user->profile->company }} @endif
                    @endIf
                </p>

                <p class="text-xs text-gray-400 mt-1 italic">{{ $user->headline ?? $roleLabel }}</p>

                <div class="flex flex-wrap gap-4 mt-4 text-sm text-gray-600 border-b border-gray-50 pb-5">
                    <span><strong>{{ $postsCount }}</strong> Postingan</span>
                    <span><strong>{{ strtoupper($user->role === 'mahasiswa' ? 'NIM' : ($user->role === 'alumni' ? 'NIM / ID' : 'NIP / ID')) }}:</strong> {{ $user->nim ?? '-' }}</span>
                    <span><strong>{{ $user->role === 'mahasiswa' ? 'Angkatan' : 'Tahun Bergabung' }}:</strong> {{ $user->profile?->angkatan ?? $user->created_at->format('Y') }}</span>
                </div>
            </div>

            {{-- Section Khusus Skripsi / Tugas Akhir --}}
            @if(in_array($user->role, ['alumni', 'mahasiswa']) && ($user->profile?->thesis_title || $user->profile?->pembimbing_1))
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100/50 mt-6">
                <h3 class="font-bold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <span>🎓</span> Informasi Tugas Akhir / Skripsi
                </h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Judul Skripsi</p>
                        <p class="text-sm text-gray-700 font-medium leading-relaxed italic">
                            "{{ $user->profile?->thesis_title ?? 'Belum mengisi judul skripsi.' }}"
                        </p>
                    </div>
                    @if ($user->profile?->pembimbing_1)
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dosen Pembimbing 1</p>
                            <p class="text-sm text-gray-700 font-medium">
                                {{ $user->profile->pembimbing_1 }}
                            </p>
                        </div>
                    @endif
                    @if ($user->profile?->pembimbing_2)
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dosen Pembimbing 2</p>
                            <p class="text-sm text-gray-700 font-medium">
                                {{ $user->profile->pembimbing_2 }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            @endif
            
        </div>
    </div>

    {{-- Section Tentang Saya --}}
    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
        <h3 class="font-bold text-gray-900 mb-4">Tentang Saya</h3>
        <p class="text-gray-600 text-sm leading-relaxed">
            {{ $user->bio ?? 'Belum ada deskripsi profil.' }}
        </p>
    </div>
</div>