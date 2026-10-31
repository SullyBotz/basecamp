@section('title', 'Karir')

<?php
use App\Models\Job;
use App\Models\JobLike;
use App\Models\JobComment;
use App\Models\Notification;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; 
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;
    use WithFileUploads; 

    // State UI
    public $selectedJob = null;
    public $showCreateModal = false;

    // Form Fields
    public $title = '';
    public $company = '';
    public $location = '';
    public $description = '';
    public $link = '';
    public $image; // 💡 PERBAIKAN 1: Ganti nama variabel jadi $image

    // State Komentar & Audio
    public $newComment = '';
    public $replyState = null;
    public $lastJobCount = 0;

    public function with()
    {
        $isInternal = in_array(auth()->user()->role ?? '', ['admin', 'dekan', 'kaprodi']);
        $query = $isInternal ? Job::with(['user', 'likes', 'comments']) : Job::with(['user', 'likes', 'comments'])->where('is_verified', true);

        // LOGIKA SUARA (Auto play jika ada loker baru)
        $currentJobCount = $query->count();
        if ($this->lastJobCount > 0 && $currentJobCount > $this->lastJobCount) {
            $this->dispatch('play-job-sound'); 
        }
        $this->lastJobCount = $currentJobCount; 

        return [
            'jobs' => $query->latest()->paginate(10),
        ];
    }

    // ==========================================
    // 1. FUNGSI POSTING LOKER
    // ==========================================
    public function storeJob()
    {
        $this->validate([
            'title'       => 'required|min:3|max:255',
            'company'     => 'required|max:255',
            'location'    => 'required|string',
            'description' => 'required|min:10',
            // 💡 PERBAIKAN 2: Validasi menggunakan properti 'image'
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 
            'link'        => 'nullable|url' 
        ]);

        $path = null;
        if ($this->image) {
            // 💡 PERBAIKAN 3: Simpan file dari properti $image
            $path = $this->image->store('jobs', 'public');
        }

        $isAutoVerified = in_array(auth()->user()->role ?? '', ['admin', 'dekan', 'kaprodi']);

        $job = Job::create([
            'user_id'     => auth()->id(),
            'title'       => $this->title,
            'company'     => $this->company,
            'location'    => $this->location,
            'description' => $this->description,
            'link'        => $this->link,
            // 💡 PERBAIKAN 4: Masukkan $path ke kolom database image_path
            'image_path'  => $path,
            'is_verified' => $isAutoVerified, 
            
            // Bypass kolom agar DB tidak teriak
            'type'        => '-',
            'salary'      => '-',
        ]);

        if (!$isAutoVerified) {
            // Notif ke dekan/admin untuk minta verifikasi
            $admins = collect(User::whereIn('role', ['admin', 'dekan', 'kaprodi'])->get());
            $notifs = [];
            $now = now();
            
            foreach ($admins as $admin) {
                $notifs[] = [
                    'user_id'    => $admin->id,
                    'title'      => 'Loker Baru Perlu Verifikasi 💼',
                    'message'    => auth()->user()->name . ' mengajukan loker: ' . $this->title,
                    'type'       => 'info', 
                    'link'       => '/dekan/jobs',  
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if(!empty($notifs)) Notification::insert($notifs);
            
            session()->flash('message', 'Lowongan berhasil dikirim! Menunggu verifikasi pihak fakultas/prodi.');
        } else {
            // Jika Dekan/Admin yang posting, langsung sebar notif ke SEMUA USER
            $users = User::where('id', '!=', auth()->id())->get(['id']);
            $now = now();
            $notifs = [];

            foreach ($users as $user) {
                $notifs[] = [
                    'user_id'    => $user->id,
                    'title'      => 'Lowongan Kerja Baru! 🔥',
                    'message'    => 'Ada loker baru sebagai ' . $job->title . ' di ' . $job->company . '.',
                    'type'       => 'loker_baru',
                    'link'       => '/karir',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            foreach (array_chunk($notifs, 500) as $chunk) {
                Notification::insert($chunk);
            }

            session()->flash('message', 'Lowongan berhasil diterbitkan secara langsung!');
        }

        $this->dispatch('play-job-sound');
        // 💡 PERBAIKAN 5: Reset state 'image'
        $this->reset(['title','company', 'location', 'description', 'link', 'image', 'showCreateModal']);
    }

    public function verifyJob($id)
    {
        if (!in_array(auth()->user()->role ?? '', ['admin', 'dekan', 'kaprodi'])) return;

        $job = Job::find($id);
        if ($job) {
            $job->update(['is_verified' => true]);
            
            $now = now();
            $notifs = [];

            $notifs[] = [
                'user_id'    => $job->user_id,
                'title'      => 'Loker Disetujui ✅',
                'message'    => 'Lowongan ' . $job->title . ' Anda telah diterbitkan ke publik.',
                'type'       => 'info',
                'link'       => '/karir',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $users = User::where('id', '!=', $job->user_id)->get(['id']);
            foreach ($users as $user) {
                $notifs[] = [
                    'user_id'    => $user->id,
                    'title'      => 'Lowongan Kerja Baru! 🔥',
                    'message'    => 'Ada loker baru sebagai ' . $job->title . ' di ' . $job->company . '.',
                    'type'       => 'loker_baru',
                    'link'       => '/karir',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($notifs, 500) as $chunk) {
                Notification::insert($chunk);
            }

            $this->dispatch('play-job-sound');
            $this->showDetail($id); 
        }
    }

    // ==========================================
    // 2. FUNGSI DETAIL, LIKE, DAN KOMENTAR LOKER
    // ==========================================
    public function showDetail($id) { 
        $this->selectedJob = Job::with([
            'user', 
            'likes', 
            'comments' => function($q) {
                $q->whereNull('parent_id')->with(['user', 'replies.user'])->latest();
            }
        ])->find($id); 
    }
    
    public function closeModal() { 
        $this->selectedJob = null; 
        $this->replyState = null;
        $this->newComment = '';
        $this->showCreateModal = false;
    }

    public function toggleLike($jobId)
    {
        $job = Job::findOrFail($jobId);
        $existingLike = $job->likes()->where('user_id', auth()->id())->first();

        if ($existingLike) {
            $existingLike->delete();
        } else {
            $job->likes()->create(['user_id' => auth()->id()]);
            
            if ($job->user_id !== auth()->id()) {
                Notification::create([
                    'user_id' => $job->user_id,
                    'title'   => 'Suka Loker Anda 👍',
                    'message' => auth()->user()->name . ' menyukai info lowongan Anda.',
                    'type'    => 'info',
                    'link'    => '/karir',
                ]);
            }
        }

        if ($this->selectedJob && $this->selectedJob->id == $jobId) {
            $this->showDetail($jobId);
        }
    }

    public function setReply($commentId) { $this->replyState = $commentId; }
    public function cancelReply() { $this->replyState = null; }

    public function storeComment($jobId)
    {
        $this->validate(['newComment' => 'required|min:2|max:500']);

        $job = Job::findOrFail($jobId);
        $dbParentId = null;
        $targetUser = null;
        $content = trim($this->newComment);

        if ($this->replyState) {
            $targetComment = JobComment::find($this->replyState);
            if ($targetComment) {
                $dbParentId = $targetComment->parent_id ?? $targetComment->id;
                $targetUser = $targetComment->user;

                if ($targetComment->parent_id) {
                    $content = '@' . str_replace(' ', '', $targetUser->name) . ' ' . $content;
                }
            }
        }

        $job->comments()->create([
            'user_id'   => auth()->id(),
            'parent_id' => $dbParentId,
            'content'   => $content,
        ]);

        $commenterName = auth()->user()->name;
        if ($targetUser) {
            if ($targetUser->id !== auth()->id()) {
                Notification::create([
                    'user_id' => $targetUser->id,
                    'title'   => 'Balasan Loker 💬',
                    'message' => $commenterName . ' membalas komentar Anda di info lowongan.',
                    'type'    => 'info',
                    'link'    => '/karir',
                ]);
            }
        } else {
            if ($job->user_id !== auth()->id()) {
                Notification::create([
                    'user_id' => $job->user_id,
                    'title'   => 'Komentar Loker 💬',
                    'message' => $commenterName . ' bertanya/berkomentar di info lowongan Anda.',
                    'type'    => 'info',
                    'link'    => '/karir',
                ]);
            }
        }

        $this->newComment = '';
        $this->replyState = null;
        $this->showDetail($jobId); 
    }
}; ?>

<div class="space-y-6 p-4 max-w-4xl mx-auto" {{ !$showCreateModal && !$selectedJob ? 'wire:poll.10s' : '' }}>
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100 gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-900 italic tracking-tight">Pusat Karir</h2>
            <p class="text-sm text-gray-500 font-medium">Berbagi peluang dan loker bersama jaringan alumni.</p>
        </div>
        <button wire:click="$set('showCreateModal', true)"
            class="bg-blue-600 text-white px-6 py-3 rounded-2xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" /></svg>
            Bagikan Lowongan
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-200 flex items-center gap-2">
            <span>✅</span> {{ session('message') }}
        </div>
    @endif

    {{-- FEED LOWONGAN KERJA --}}
    <div class="grid grid-cols-1 gap-6">
        @forelse ($jobs as $job)
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden p-6 transition-all hover:shadow-md {{ !$job->is_verified ? 'bg-orange-50/10 border-orange-100' : '' }}">
                
                {{-- User Info --}}
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ $job->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($job->user?->name ?? 'U') }}" class="w-10 h-10 rounded-full border border-gray-100 object-cover">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-bold text-gray-900 text-sm">{{ $job->user?->name ?? 'Pengguna Dihapus' }}</p>
                            @if (!$job->is_verified)
                                <span class="text-[9px] bg-orange-200 text-orange-800 px-2 py-0.5 rounded-full font-black uppercase tracking-wider">Menunggu Verifikasi</span>
                            @endif
                        </div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                            {{ $job->user?->role ?? '-' }} • {{ $job->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                {{-- Konten Postingan (Summary) --}}
                <div class="mb-3">
                    <h3 class="text-lg font-black text-gray-900 leading-tight mb-1">{{ $job->title }}</h3>
                    <h3 class="text-lg font-black text-gray-900 leading-tight mb-1">{{ $job->company }}</h3>
                    <p class="text-xs text-blue-600 font-bold mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        {{ $job->location }}
                    </p>
                    <p class="text-sm text-gray-700 leading-relaxed line-clamp-3 whitespace-pre-line">{{ $job->description }}</p>
                </div>

                @if($job->image_path)
                    <div class="relative w-full h-48 sm:h-64 rounded-2xl overflow-hidden border border-gray-50 mb-4 bg-gray-100">
                        <img src="{{ asset('storage/' . $job->image_path) }}" class="w-full h-full object-cover">
                    </div>
                @endif

                {{-- Action Bar --}}
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50">
                    <div class="flex gap-4">
                        @php
                            $isLiked = $job->likes->contains('user_id', auth()->id());
                        @endphp
                        <!-- <button wire:click="toggleLike({{ $job->id }})" class="flex items-center gap-1.5 transition {{ $isLiked ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}">
                            <svg class="w-5 h-5" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                            <span class="text-xs font-bold">{{ $job->likes->count() }}</span>
                        </button> -->
                        
                        <button wire:click="showDetail({{ $job->id }})" class="flex items-center gap-1.5 text-gray-400 hover:text-blue-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.43 3 11.996c0 2.29.5 4.492 1.342 6.302a8.887 8.887 0 0 1-1.678 2.067 1.05 1.05 0 0 0 .584 1.834c1.554.265 3.033-.186 4.148-.797C8.618 19.866 10.263 20.25 12 20.25Z" /></svg>
                            <span class="text-xs font-bold">{{ $job->comments->count() }}</span>
                        </button>
                    </div>

                    <button wire:click="showDetail({{ $job->id }})" class="text-xs font-black text-blue-600 bg-blue-50 px-4 py-2 rounded-xl hover:bg-blue-600 hover:text-white transition uppercase tracking-wider">
                        Lihat Info Detail
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
                <p class="text-gray-400 font-medium italic">Belum ada lowongan tersedia saat ini.</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-4">{{ $jobs->links() }}</div>

    {{-- MODAL: BUAT LOKER --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden p-8 max-h-[90vh] overflow-y-auto border-4 border-white">
            <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Bagikan Info Loker
            </h3>
            <form wire:submit.prevent="storeJob" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Judul Pekerjaan</label>
                    <input wire:model="title" type="text" placeholder="Contoh: Staff IT / Marketing..." class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold">
                    @error('title') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Penyedia Pekerjaan</label>
                    <input wire:model="company" type="text" placeholder="Contoh: PT. MBG" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold">
                    @error('company') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Lokasi Penempatan</label>
                    <input wire:model="location" type="text" placeholder="Kota / Remote" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
                    @error('location') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Deskripsi Detail</label>
                    <textarea wire:model="description" placeholder="Ceritakan detail posisi, kualifikasi, dll..." class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 min-h-[120px]"></textarea>
                    @error('description') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Brosur / Banner (Opsional)</label>
                    <input type="file" wire:model="image" accept="image/*" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div wire:loading wire:target="image" class="text-xs text-blue-500 mt-1 pl-2 font-medium">⏳ Mengunggah gambar...</div>
                    @error('image') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 pl-2">Link Pendaftaran (Opsional)</label>
                    <input wire:model="link" type="url" placeholder="Wajib pakai https://..." class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500">
                    @error('link') <span class="text-red-500 text-xs mt-1 block pl-2">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeModal" class="flex-1 py-3 text-sm font-bold text-gray-500 bg-gray-100 rounded-2xl hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" class="flex-[2] bg-blue-600 text-white px-8 py-3 rounded-2xl text-sm font-black tracking-wide hover:bg-blue-700 transition shadow-lg shadow-blue-200 disabled:opacity-50">Posting Loker</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL: DETAIL LOKER & KOMENTAR BERJENJANG --}}
    @if($selectedJob)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl flex flex-col max-h-full overflow-hidden">
            
            {{-- Header Detail --}}
            <div class="p-6 bg-white border-b border-gray-100 flex justify-between items-start shrink-0">
                <div class="flex items-center gap-3">
                    <img src="{{ $selectedJob->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($selectedJob->user?->name ?? 'U') }}" class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 leading-tight">{{ $selectedJob->title }}</h3>
                        <h3 class="text-xl font-black text-gray-900 leading-tight">{{ $selectedJob->company }}</h3>
                        <p class="text-sm font-bold text-blue-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            {{ $selectedJob->location }}
                        </p>
                    </div>
                </div>
                <button wire:click="closeModal" class="bg-gray-100 text-gray-500 w-8 h-8 rounded-full flex items-center justify-center font-bold hover:bg-red-500 hover:text-white transition">&times;</button>
            </div>

            {{-- Area Scroll (Konten + Komentar) --}}
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50">
                
                {{-- Detail Isi Postingan --}}
                <div class="mb-6">
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-4">
                        {{ $selectedJob->description }}
                    </div>

                    @if($selectedJob->image_path)
                        <div class="mb-4">
                            <img src="{{ asset('storage/' . $selectedJob->image_path) }}" 
                                 class="w-full rounded-3xl border border-gray-100 shadow-sm"
                                 alt="Brosur Lowongan">
                        </div>
                    @endif

                    <div class="flex gap-3">
                        @if (in_array(auth()->user()->role ?? '', ['admin', 'dekan', 'kaprodi']) && !$selectedJob->is_verified)
                            <button wire:click="verifyJob({{ $selectedJob->id }})" class="flex-1 py-3 text-xs font-black tracking-widest text-white uppercase bg-green-600 rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-200">
                                Verifikasi Loker Ini
                            </button>
                        @endif

                        @if($selectedJob->link)
                            <a href="{{ $selectedJob->link }}" target="_blank" class="flex-1 py-3 text-xs font-black tracking-widest text-white uppercase bg-blue-600 rounded-xl hover:bg-blue-700 transition text-center shadow-lg shadow-blue-200">
                                Kunjungi Tautan Pendaftaran
                            </a>
                        @endif
                    </div>
                </div>

                <hr class="border-gray-200 my-6">
                
                <h4 class="text-sm font-black text-gray-900 mb-4 flex items-center gap-2 uppercase tracking-wider">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                    Diskusi & Komentar ({{ $selectedJob->comments->count() }})
                </h4>

                {{-- DAFTAR KOMENTAR BERJENJANG --}}
                <div class="space-y-5">
                    @forelse($selectedJob->comments as $comment)
                        <div>
                            {{-- Komentar Induk --}}
                            <div class="flex gap-3 items-start">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($comment->user?->name ?? 'U') }}&background=eff6ff&color=2563eb" class="w-8 h-8 rounded-full shrink-0">
                                <div class="w-full">
                                    <div class="bg-white rounded-2xl rounded-tl-none p-3 border border-gray-100 shadow-sm">
                                        <div class="flex justify-between items-start mb-1">
                                            <p class="text-xs font-bold text-gray-900">{{ $comment->user?->name ?? 'Pengguna Dihapus' }}</p>
                                            <span class="text-[9px] text-gray-400 font-bold">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-700 leading-relaxed">{{ $comment->content }}</p>
                                    </div>
                                    <button wire:click="setReply({{ $comment->id }})" class="text-[10px] font-bold text-gray-400 hover:text-blue-600 mt-1.5 ml-2 uppercase tracking-wide">Balas</button>
                                </div>
                            </div>

                            {{-- Sub-Komentar (Level 2) --}}
                            @if($comment->replies->count() > 0)
                                <div class="ml-11 mt-3 space-y-3 border-l-2 border-blue-100 pl-3">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex gap-2 items-start">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($reply->user?->name ?? 'U') }}&background=f0fdf4&color=16a34a" class="w-6 h-6 rounded-full shrink-0">
                                            <div class="w-full">
                                                <div class="bg-blue-50/50 border border-blue-100/50 rounded-2xl rounded-tl-none p-2.5 w-full">
                                                    <div class="flex justify-between items-start mb-1">
                                                        <p class="text-[11px] font-bold text-gray-900">{{ $reply->user?->name ?? 'Pengguna Dihapus' }}</p>
                                                        <span class="text-[8px] text-gray-400 font-bold">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="text-[11px] text-gray-700 leading-relaxed">
                                                        @if(str_starts_with($reply->content, '@'))
                                                            @php $parts = explode(' ', $reply->content, 2); @endphp
                                                            <span class="text-blue-600 font-bold">{{ $parts[0] }}</span> {{ $parts[1] ?? '' }}
                                                        @else
                                                            {{ $reply->content }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <button wire:click="setReply({{ $reply->id }})" class="text-[9px] font-bold text-gray-400 hover:text-blue-600 mt-1 ml-2 uppercase tracking-wide">Balas</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-4 bg-white rounded-2xl border border-gray-100">Belum ada diskusi untuk loker ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Ketik Komentar --}}
            <div class="p-4 bg-white border-t border-gray-100 shrink-0">
                @if($replyState)
                    @php $parentComment = \App\Models\JobComment::find($replyState); @endphp
                    <div class="flex justify-between items-center mb-2 px-3 py-1.5 bg-blue-50 rounded-lg border border-blue-100">
                        <span class="text-[10px] text-blue-600 font-bold">Membalas @ {{ $parentComment->user?->name ?? 'Pengguna Dihapus' }}...</span>
                        <button wire:click="cancelReply" class="text-[10px] text-gray-400 hover:text-red-500 font-bold">&times; Batal</button>
                    </div>
                @endif

                <div class="flex gap-3">
                    <img src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()?->name ?? 'U') }}" class="w-10 h-10 rounded-full shrink-0">
                    <div class="w-full relative">
                        <input wire:model="newComment" wire:keydown.enter="storeComment({{ $selectedJob->id }})" type="text" 
                            placeholder="{{ $replyState ? 'Tulis balasan Anda...' : 'Tanyakan sesuatu tentang loker ini...' }}" 
                            class="w-full bg-gray-50 border-none rounded-full px-4 py-2.5 text-xs focus:ring-2 focus:ring-blue-500 transition pr-12">
                        
                        <button wire:click="storeComment({{ $selectedJob->id }})" class="absolute right-2 top-1.5 text-white bg-blue-600 hover:bg-blue-700 p-1.5 rounded-full transition shadow-md">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" /></svg>
                        </button>
                    </div>
                </div>
                @error('newComment') <span class="text-[10px] text-red-500 font-medium ml-12 mt-1 block">{{ $message }}</span> @enderror
            </div>
            
        </div>
    </div>
    @endif

    {{-- AUDIO NOTIFIKASI --}}
    <audio id="jobNotifAudio" preload="auto">
        <source src="{{ asset('storage/audio/jawa-kobo.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        document.addEventListener('livewire:initialized', () => {
            window.addEventListener('play-job-sound', () => {
                let audio = document.getElementById('jobNotifAudio');
                if (audio) {
                    audio.play().catch(function(error) {
                        console.log('Autoplay ditahan browser. Harap interaksi dengan layar.');
                    });
                }
            });
        });
    </script>
</div>