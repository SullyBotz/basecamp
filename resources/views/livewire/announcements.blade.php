@section('title', 'Pengumuman Fakultas dan Prodi')
<?php
use App\Models\User;
use App\Models\OfficialBroadcast;
use App\Models\BroadcastLike;
use App\Models\BroadcastComment;
use App\Models\Notification; 
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads; 

new #[Layout('layouts.app')] 
class extends Component {
    use WithFileUploads; 

    public string $title = '';
    public string $content = '';
    public $image;

    public array $newComments = [];
    public array $replyState = []; 

    public function with()
    {
        return [
            'broadcasts' => OfficialBroadcast::with([
                'likes', 
                'comments' => function($query) {
                    $query->whereNull('parent_id')->with(['user', 'replies.user'])->latest();
                }
            ])->latest()->get(),
        ];
    }

    // ==========================================
    // FUNGSI PENGUMUMAN & NOTIFIKASI MASSAL
    // ==========================================
    public function storeBroadcast()
    {
        if (!in_array(auth()->user()->role, ['dekan', 'kaprodi'])) abort(403);
        
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'image' => 'nullable|image|max:2048', 
        ]);
        
        $imagePath = $this->image ? $this->image->store('broadcasts', 'public') : null;
        
        $broadcast = OfficialBroadcast::create([
            'admin_id' => auth()->id(),
            'title' => trim($this->title),
            'content' => trim($this->content),
            'image_path' => $imagePath,
        ]);

        $allUsers = User::where('id', '!=', auth()->id())->pluck('id');
        $notificationsData = [];
        $now = now();
        
        foreach ($allUsers as $userId) {
            $notificationsData[] = [
                'user_id'    => $userId,
                'title'      => 'Pengumuman Resmi Baru',
                'message'    => 'Fakultas/Prodi baru saja menerbitkan pengumuman: ' . $broadcast->title,
                'type'       => 'info', 
                'is_read'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        if (!empty($notificationsData)) {
            Notification::insert($notificationsData);
        }

        $this->reset(['title', 'content', 'image']);
        session()->flash('success', 'Pengumuman resmi berhasil diterbitkan!');
    }

    // ==========================================
    // FUNGSI LIKE & NOTIFIKASI
    // ==========================================
    public function toggleLike($broadcastId)
    {
        $userId = auth()->id();
        $broadcast = OfficialBroadcast::findOrFail($broadcastId);
        $existingLike = $broadcast->likes()->where('user_id', $userId)->first();
        
        if ($existingLike) { 
            $existingLike->delete(); 
        } else { 
            $broadcast->likes()->create(['user_id' => $userId]); 

            if ($broadcast->admin_id !== $userId) {
                Notification::create([
                    'user_id' => $broadcast->admin_id,
                    'title'   => 'Suka Baru',
                    'message' => auth()->user()->name . ' menyukai pengumuman Anda: ' . $broadcast->title,
                    'type'    => 'info',
                    'is_read' => 0,
                ]);
            }
        }
    }

    public function setReply($broadcastId, $commentId)
    {
        $this->replyState[$broadcastId] = $commentId;
    }

    public function cancelReply($broadcastId)
    {
        $this->replyState[$broadcastId] = null;
    }

    // ==========================================
    // FUNGSI KOMENTAR SUPER & NOTIFIKASI
    // ==========================================
    public function storeComment($broadcastId)
    {
        $this->validate([
            "newComments.$broadcastId" => 'required|string|max:500'
        ], ["newComments.$broadcastId.required" => 'Komentar tidak boleh kosong.']);

        $broadcast = OfficialBroadcast::findOrFail($broadcastId);
        $targetId = $this->replyState[$broadcastId] ?? null;

        $dbParentId = null;
        $targetUser = null;
        $content = trim($this->newComments[$broadcastId]);

        if ($targetId) {
            $targetComment = BroadcastComment::find($targetId);
            if ($targetComment) {
                // 💡 Menjaga kedalaman hanya 2 level agar UI tetap rapi
                // Jika komentar yang dibalas adalah balasan juga, kita pautkan ke parent utamanya
                $dbParentId = $targetComment->parent_id ?? $targetComment->id;
                $targetUser = $targetComment->user;

                // 💡 Otomatis tambahkan "@nama_user" jika membalas komentar orang lain (level 2)
                if ($targetComment->parent_id) {
                    $content = '@' . str_replace(' ', '', $targetUser->name) . ' ' . $content;
                }
            }
        }

        // Simpan Komentar
        $broadcast->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $dbParentId,
            'content' => $content,
        ]);

        $commenterName = auth()->user()->name;

        // 💡 DISTRIBUSI NOTIFIKASI YANG AKURAT
        if ($targetUser) {
            // Notifikasi untuk user yang komentarnya dibalas (level 1 atau level 2)
            if ($targetUser->id !== auth()->id()) {
                Notification::create([
                    'user_id' => $targetUser->id,
                    'title'   => 'Balasan Komentar',
                    'message' => $commenterName . ' membalas komentar Anda di pengumuman: ' . $broadcast->title,
                    'type'    => 'info',
                    'is_read' => 0,
                ]);
            }
        } else {
            // Notifikasi untuk Admin jika itu komentar baru (bukan balasan)
            if ($broadcast->admin_id !== auth()->id()) {
                Notification::create([
                    'user_id' => $broadcast->admin_id,
                    'title'   => 'Komentar Baru',
                    'message' => $commenterName . ' mengomentari pengumuman Anda: ' . $broadcast->title,
                    'type'    => 'info',
                    'is_read' => 0,
                ]);
            }
        }

        // Reset
        $this->newComments[$broadcastId] = '';
        $this->replyState[$broadcastId] = null;
    }
}; ?>

<div class="max-w-3xl mx-auto py-8 px-4">
    {{-- Header Papan Pengumuman --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-purple-700 italic tracking-tighter uppercase">Papan Pengumuman</h1>
            <p class="text-sm text-gray-500 font-medium">Informasi resmi langsung dari fakultas dan prodi.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    {{-- Form Dekan / Kaprodi --}}
    @if(in_array(auth()->user()->role, ['dekan', 'kaprodi']))
        <div class="bg-white rounded-3xl border-2 border-purple-100 shadow-sm p-6 mb-8">
            <h3 class="text-sm font-black text-purple-700 uppercase tracking-wider mb-4">Buat Pengumuman Baru</h3>
            <form wire:submit="storeBroadcast" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Judul Pengumuman</label>
                    <input wire:model="title" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Isi Pengumuman</label>
                    <textarea wire:model="content" rows="4" class="w-full rounded-xl border-gray-200 text-sm focus:ring-purple-500 whitespace-pre-line"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gambar Pendukung</label>
                    <input wire:model="image" type="file" accept="image/*" class="text-xs file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-purple-50 file:text-purple-700">
                    @if ($image) <div class="mt-3 w-full h-40 rounded-xl overflow-hidden border"><img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover"></div> @endif
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs uppercase px-6 py-2.5 rounded-xl shadow-md"><span wire:loading.remove>Terbitkan Info</span><span wire:loading>Memproses...</span></button>
                </div>
            </form>
        </div>
        <hr class="border-gray-100 mb-8">
    @endif

    {{-- DAFTAR LIST PENGUMUMAN --}}
    <div class="space-y-6">
        @forelse($broadcasts as $bc)
            <div class="bg-white rounded-3xl border-2 border-purple-50 shadow-sm overflow-hidden">
                @if($bc->image_path)
                    <img src="{{ asset('storage/' . $bc->image_path) }}" class="w-full h-64 object-cover">
                @endif
                
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="bg-purple-600 text-white text-[9px] font-black px-2 py-0.5 rounded italic">OFFICIAL</span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase">{{ $bc->created_at->format('d M Y') }}</span>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 mb-3">{{ $bc->title }}</h2>
                    <div class="text-gray-600 text-sm leading-relaxed whitespace-pre-line mb-6">
                        {{ $bc->content }}
                    </div>

                    {{-- Interaksi Like & Comment --}}
                    <div class="flex items-center gap-4 border-t border-b border-gray-50 py-3 mt-4">
                        @php
                            $isLiked = $bc->likes->contains('user_id', auth()->id());
                            $totalComments = \App\Models\BroadcastComment::where('official_broadcast_id', $bc->id)->count();
                        @endphp
                        <!-- <button wire:click="toggleLike({{ $bc->id }})" class="flex items-center gap-1.5 {{ $isLiked ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}">
                            <svg class="w-5 h-5" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                            <span class="text-xs font-bold">{{ $bc->likes->count() }} Suka</span>
                        </button> -->
                        <div class="flex items-center gap-1.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.43 3 11.996c0 2.29.5 4.492 1.342 6.302a8.887 8.887 0 0 1-1.678 2.067 1.05 1.05 0 0 0 .584 1.834c1.554.265 3.033-.186 4.148-.797C8.618 19.866 10.263 20.25 12 20.25Z" /></svg>
                            <span class="text-xs font-bold">{{ $totalComments }} Komentar</span>
                        </div>
                    </div>

                    {{-- DAFTAR KOMENTAR & BALASAN --}}
                    @if($bc->comments->count() > 0)
                        <div class="mt-4 space-y-5 max-h-80 overflow-y-auto pr-2">
                            @foreach($bc->comments as $comment)
                                <div>
                                    <div class="flex gap-3 items-start">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=f3e8ff&color=7e22ce" class="w-8 h-8 rounded-full shrink-0">
                                        <div class="w-full">
                                            <div class="bg-gray-50 rounded-2xl rounded-tl-none p-3">
                                                <div class="flex justify-between items-start mb-1">
                                                    <p class="text-xs font-bold text-gray-900">{{ $comment->user->name }}</p>
                                                    <span class="text-[9px] font-bold text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-xs text-gray-700 leading-relaxed">{{ $comment->content }}</p>
                                            </div>
                                            <button wire:click="setReply({{ $bc->id }}, {{ $comment->id }})" class="text-[10px] font-bold text-gray-400 hover:text-purple-600 mt-1.5 ml-2 uppercase tracking-wide">Balas</button>
                                        </div>
                                    </div>

                                    {{-- 💡 BALASAN LEVEL 2 (Sekarang ada tombol balasnya) --}}
                                    @if($comment->replies->count() > 0)
                                        <div class="ml-11 mt-3 space-y-3 border-l-2 border-purple-100 pl-3">
                                            @foreach($comment->replies as $reply)
                                                <div class="mb-2">
                                                    <div class="flex gap-2 items-start">
                                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($reply->user->name) }}&background=e0e7ff&color=4338ca" class="w-6 h-6 rounded-full shrink-0">
                                                        <div class="w-full">
                                                            <div class="bg-blue-50/50 rounded-2xl rounded-tl-none p-2.5 w-full">
                                                                <div class="flex justify-between items-start mb-1">
                                                                    <p class="text-[11px] font-bold text-gray-900">{{ $reply->user->name }}</p>
                                                                    <span class="text-[8px] font-bold text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                                                </div>
                                                                <p class="text-[11px] text-gray-700 leading-relaxed">
                                                                    {{-- 💡 Otomatis highlight teks @nama_user --}}
                                                                    @if(str_starts_with($reply->content, '@'))
                                                                        @php $parts = explode(' ', $reply->content, 2); @endphp
                                                                        <span class="text-purple-600 font-bold">{{ $parts[0] }}</span> {{ $parts[1] ?? '' }}
                                                                    @else
                                                                        {{ $reply->content }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                            {{-- Tombol Balas untuk Sub-Komentar --}}
                                                            <button wire:click="setReply({{ $bc->id }}, {{ $reply->id }})" class="text-[9px] font-bold text-gray-400 hover:text-purple-600 mt-1 ml-2 uppercase tracking-wide">
                                                                Balas
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Form Tulis Komentar / Balasan --}}
                    <div class="mt-4 pt-4 border-t border-gray-50">
                        @if(isset($replyState[$bc->id]) && $replyState[$bc->id])
                            @php $parentComment = \App\Models\BroadcastComment::find($replyState[$bc->id]); @endphp
                            <div class="flex justify-between items-center mb-2 px-2 py-1 bg-purple-50 rounded-lg border border-purple-100">
                                <span class="text-[10px] text-purple-600 font-bold">Membalas @ {{ $parentComment->user->name }}...</span>
                                <button wire:click="cancelReply({{ $bc->id }})" class="text-[10px] text-gray-400 hover:text-red-500 font-bold">&times; Batal</button>
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=f3e8ff&color=7e22ce" class="w-8 h-8 rounded-full shrink-0">
                            <div class="w-full relative">
                                <input wire:model="newComments.{{ $bc->id }}" wire:keydown.enter="storeComment({{ $bc->id }})" type="text" 
                                    placeholder="{{ isset($replyState[$bc->id]) && $replyState[$bc->id] ? 'Tulis balasan Anda...' : 'Tulis komentar Anda...' }}" 
                                    class="w-full bg-gray-50 border-transparent rounded-full px-4 py-2 text-xs focus:ring-purple-500 focus:bg-white transition pr-10">
                                
                                <button wire:click="storeComment({{ $bc->id }})" class="absolute right-2 top-1.5 text-purple-600 hover:text-purple-800 transition p-1">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" /></svg>
                                </button>
                                
                                @error("newComments.{$bc->id}") <span class="text-[10px] text-red-500 font-medium ml-3 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="text-center py-20 text-gray-400 italic bg-gray-50 rounded-3xl border-2 border-dashed">
                Belum ada pengumuman resmi.
            </div>
        @endforelse
    </div>
</div>