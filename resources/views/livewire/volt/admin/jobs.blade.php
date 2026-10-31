@section('title', 'Manajemen Lowongan Kerja')
<?php

use App\Models\Job;
use App\Models\User;
use App\Models\Notification;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; 
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;
    use WithFileUploads; 

    public $search = '';
    
    // Properti Modal & Form
    public $showCreateModal = false;
    public $title = '';
    public $company = '';
    public $location = '';
    public $description = ''; 
    public $link = '';        
    public $image_path;       

    public function with()
    {
        return [
            'jobs' => Job::with('user')
                ->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('company', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }

    private function authorizeInternal()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'dekan', 'kaprodi'])) {
            session()->flash('error', 'Anda tidak memiliki hak akses untuk aksi ini.');
            return false;
        }
        return true;
    }

    public function openModal()
    {
        if (!$this->authorizeInternal()) return;
        $this->reset(['title', 'company', 'location', 'description', 'link', 'image_path']);
        $this->showCreateModal = true;
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
    }

    public function storeJob()
    {
        if (!$this->authorizeInternal()) return;

        $this->validate([
            'title'       => 'required|string|max:255',
            'company'     => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'description' => 'required|string',
            'image_path'  => 'nullable|image|max:2048',
            'link'        => 'nullable|url',
        ]);

        // Proses upload gambar
        $path = $this->image_path ? $this->image_path->store('jobs', 'public') : null;

        $job = Job::create([
            'title'       => $this->title,
            'company'     => $this->company,
            'location'    => $this->location,
            'description' => $this->description,
            'link'        => $this->link,
            'image_path'  => $path,
            'user_id'     => auth()->id(),
            'is_verified' => true, 
            
            // 💡 OBAT ERROR-NYA DI SINI LEK: Bypass kolom lama yang wajib diisi di database
            'type'        => '-',
            'salary'      => '-', 
        ]);

        $this->notifyNewJobToAllExceptMe($job);
        $this->showCreateModal = false;
        session()->flash('message', 'Lowongan kerja baru berhasil ditambahkan!');
    }

    private function notifyNewJobToAllExceptMe($job)
    {
        $now = now();
        $users = User::where('id', '!=', auth()->id())->get(['id']);
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = [
                'user_id'    => $user->id,
                'title'      => 'Lowongan Kerja Baru! 🔥',
                'message'    => 'Ada loker baru sebagai ' . $job->title . ' di ' . $job->company . '.',
                'type'       => 'loker_baru',
                'link'       => '/karir',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Simpan massal (Chunk per 500 baris agar ringan)
        foreach (array_chunk($notifications, 500) as $chunk) {
            Notification::insert($chunk);
        }
    }

    public function acceptJob($id)
    {
        if (!$this->authorizeInternal()) return;
        $job = Job::find($id);
        if ($job) {
            $job->update(['is_verified' => true]);
            session()->flash('message', 'Lowongan kerja berhasil disetujui!');
        }
    }

    public function rejectJob($id)
    {
        if (!$this->authorizeInternal()) return;
        Job::find($id)?->delete();
        session()->flash('message', 'Lowongan kerja telah ditolak dan dihapus.');
    }

    public function deleteJob($id)
    {
        if (!$this->authorizeInternal()) return;
        Job::find($id)?->delete();
        session()->flash('message', 'Lowongan kerja berhasil dihapus.');
    }
};
?>

{{-- VIEW --}}
<div class="space-y-6 max-w-6xl mx-auto py-8 px-4">
    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded-2xl shadow-sm mb-4 text-sm flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button type="button" class="text-green-500 hover:text-green-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    {{-- Stats & Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span class="p-2 bg-purple-100 text-purple-600 rounded-xl">💼</span>
                Manajemen Karir
            </h2>
            <p class="text-sm text-gray-500 mt-1">Verifikasi pengajuan karir.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <input wire:model.live="search" type="text" placeholder="Cari posisi atau perusahaan..." class="rounded-2xl border-gray-200 text-sm focus:ring-purple-500 focus:border-purple-500">
            
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm"><p class="text-[10px] font-bold text-gray-400 uppercase">Total</p><h4 class="text-2xl font-black">{{ \App\Models\Job::count() }}</h4></div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm"><p class="text-[10px] font-bold text-gray-400 uppercase">Menunggu Verifikasi</p><h4 class="text-2xl font-black text-yellow-600">{{ \App\Models\Job::where('is_verified', false)->count() }}</h4></div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm"><p class="text-[10px] font-bold text-gray-400 uppercase">Telah Diverifikasi</p><h4 class="text-2xl font-black text-green-600">{{ \App\Models\Job::where('is_verified', true)->count() }}</h4></div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase">Lowongan</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase text-center">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($jobs as $job)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm">{{ $job->title }}</p>
                            <p class="text-xs text-gray-500">{{ $job->company }}</p>
                        </td>
                        {{-- KOLOM STATUS --}}
                        <td class="px-6 py-4 text-center">
                            @if($job->is_verified)
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase">Terverifikasi</span>
                            @else
                                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase">Belum Diverifikasi</span>
                            @endif
                        </td>
                        
                        {{-- KOLOM AKSI --}}
                        <td class="px-6 py-4 text-right">
                            @if(!$job->is_verified)
                                <div class="flex justify-end gap-2">
                                    <button wire:click="acceptJob({{ $job->id }})" class="bg-emerald-500 text-white px-3 py-1 rounded-xl text-[10px] font-bold hover:bg-emerald-600 transition">Setujui</button>
                                    <button wire:click="rejectJob({{ $job->id }})" class="bg-rose-500 text-white px-3 py-1 rounded-xl text-[10px] font-bold hover:bg-rose-600 transition">Tolak</button>
                                </div>
                            @else
                                <button wire:click="deleteJob({{ $job->id }})" wire:confirm="Hapus permanen lowongan ini?" 
                                    class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-10 text-center text-gray-400 text-sm">Tidak ada lowongan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MODAL TAMBAH LOKER (Gaya Karir) --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">Bagikan Info Loker</h3>
            <form wire:submit.prevent="storeJob" class="space-y-4">
                <input wire:model="title" type="text" placeholder="Judul Pekerjaan" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm">
                <input wire:model="company" type="text" placeholder="Nama Perusahaan" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm">
                <input wire:model="location" type="text" placeholder="Lokasi Penempatan" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm">
                <textarea wire:model="description" placeholder="Deskripsi Detail..." class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm min-h-[120px]"></textarea>
                
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase pl-2 mb-1">Brosur / Banner (Opsional)</label>
                    <input type="file" wire:model="image_path" accept="image/*" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-2 text-sm">
                </div>

                <input wire:model="link" type="url" placeholder="Link Pendaftaran (Opsional)" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm">

                <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeModal" class="flex-1 py-3 text-sm font-bold text-gray-500 bg-gray-100 rounded-2xl">Batal</button>
                    <button type="submit" class="flex-[2] bg-blue-600 text-white py-3 rounded-2xl text-sm font-black hover:bg-blue-700">Posting Loker</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>