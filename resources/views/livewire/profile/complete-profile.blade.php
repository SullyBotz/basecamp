@section('title', 'Verifikasi')
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $angkatan;
    public $judul_skripsi;
    public $dosen_pembimbing_1;
    public $dosen_pembimbing_2;
    public $role;

    public function mount()
    {
        $user = auth()->user();
        $this->role = $user->role;
        
        // Mengisi nilai awal jika data sudah ada sebelumnya
        $this->angkatan = $user->angkatan;
        $this->judul_skripsi = $user->judul_skripsi;
        $this->dosen_pembimbing_1 = $user->dosen_pembimbing_1;
        $this->dosen_pembimbing_2 = $user->dosen_pembimbing_2;
    }

    public function save()
    {
        $user = auth()->user();

        // Validasi dinamis berdasarkan role user
        if ($this->role === 'alumni') {
            $this->validate([
                'angkatan' => 'nullable|string|max:10',
                'judul_skripsi' => 'nullable|string|max:500',
                'dosen_pembimbing_1' => 'nullable|string|max:255',
                'dosen_pembimbing_2' => 'nullable|string|max:255',
            ]);
        } else {
            // Jika mahasiswa, cukup validasi angkatan saja
            $this->validate([
                'angkatan' => 'nullable|string|max:10',
            ]);
        }

        // Update data ke database
        $user->update([
            'angkatan' => $this->angkatan,
            'judul_skripsi' => $this->role === 'alumni' ? $this->judul_skripsi : null,
            'dosen_pembimbing_1' => $this->role === 'alumni' ? $this->dosen_pembimbing_1 : null,
            'dosen_pembimbing_2' => $this->role === 'alumni' ? $this->dosen_pembimbing_2 : null,
            'is_profile_completed' => true, // Mengubah status agar lolos middleware
        ]);

        return redirect()->route('dashboard')->with('success', 'Profil berhasil dilengkapi!');
    }
}; ?>

<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8">
            
            <div class="mb-6">
                <h2 class="text-2xl font-black text-gray-900">Lengkapi Data Profil</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Halo, <span class="font-bold text-gray-700">{{ auth()->user()->name }}</span>. Silakan isi data berikut (bersifat opsional, dapat diisi nanti atau sekarang). (<span class="uppercase font-bold text-purple-600">{{ $role }}</span>) untuk melanjutkan ke dashboard.
                </p>
            </div>

            <form wire:submit="save" class="space-y-5">
                
                {{-- Form Angkatan (Muncul untuk Mahasiswa & Alumni) --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tahun Angkatan</label>
                    <input type="text" wire:model="angkatan" placeholder="Contoh: 2020" 
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('angkatan') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Form Khusus Alumni --}}
                @if($role === 'alumni')
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Judul Skripsi</label>
                        <textarea wire:model="judul_skripsi" rows="3" placeholder="Masukkan judul skripsi lengkap Anda..." 
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                        @error('judul_skripsi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Dosen Pembimbing 1</label>
                        <input type="text" wire:model="dosen_pembimbing_1" placeholder="Nama lengkap beserta gelar" 
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                        @error('dosen_pembimbing_1') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Dosen Pembimbing 2</label>
                        <input type="text" wire:model="dosen_pembimbing_2" placeholder="Nama lengkap beserta gelar" 
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                        @error('dosen_pembimbing_2') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="pt-4">
                    <button type="submit" 
                        class="w-full bg-purple-600 text-white py-3 px-4 rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-100">
                        Later...
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>