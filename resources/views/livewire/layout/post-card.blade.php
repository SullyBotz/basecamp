<?php

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Notification; 
use Illuminate\Support\Str; 
use Livewire\Volt\Component;

new class extends Component {
    public Post $post;
    public string $comment_text = '';
    public bool $showComments = false;
    
    // 💡 Properti baru untuk melacak target balasan
    public ?int $replyState = null; 

    public function with()
    {
        return [
            'isLiked' => $this->post
                ->likes()
                ->where('user_id', auth()->id())
                ->exists(),
            'likesCount' => $this->post->likes()->count(),
            // 💡 Ambil hanya komentar utama (parent_id = null) beserta balasannya
            'comments' => $this->showComments ? 
                $this->post->comments()
                    ->whereNull('parent_id')
                    ->with(['user', 'replies.user'])
                    ->latest()
                    ->get() : 
                collect(),
            'commentsCount' => $this->post->comments()->count(),
        ];
    }

    public function toggleLike()
    {
        $like = Like::where('post_id', $this->post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($like) {
            $like->delete();
        } else {
            Like::create([
                'post_id' => $this->post->id,
                'user_id' => auth()->id(),
            ]);

            if ($this->post->user_id !== auth()->id()) {
                Notification::create([
                    'user_id' => $this->post->user_id, 
                    'title'   => '❤️ Suka Baru',
                    'message' => auth()->user()->name . ' menyukai postingan Anda.',
                    'type'    => 'info_post', 
                    'link'    => '/post/' . $this->post->id, 
                ]);
            }
        }
    }

    public function toggleComments()
    {
        $this->showComments = !$this->showComments;
    }

    // 💡 Fungsi baru untuk memicu mode balas
    public function setReply($commentId)
    {
        $this->replyState = $commentId;
        $this->showComments = true;
    }

    // 💡 Fungsi baru untuk batal membalas
    public function cancelReply()
    {
        $this->replyState = null;
    }

    public function sendComment()
    {
        $this->validate([
            'comment_text' => 'required|min:2',
        ]);

        $commentText = trim($this->comment_text);
        
        $dbParentId = null;
        $targetUser = null;

        // 💡 Menentukan hierarki balasan
        if ($this->replyState) {
            $targetComment = Comment::find($this->replyState);
            if ($targetComment) {
                // Batasi kedalaman maksimal 2 level (rata kiri)
                $dbParentId = $targetComment->parent_id ?? $targetComment->id;
                $targetUser = $targetComment->user;

                // Tambahkan @nama_user otomatis jika membalas di dalam sub-komentar
                if ($targetComment->parent_id) {
                    $commentText = '@' . str_replace(' ', '', $targetUser->name) . ' ' . $commentText;
                }
            }
        }

        Comment::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'parent_id' => $dbParentId,
            'comment_text' => $commentText,
        ]);

        // 💡 DISTRIBUSI NOTIFIKASI
        if ($targetUser) {
            // Jika ini balasan: Notif ke pemilik komentar
            if ($targetUser->id !== auth()->id()) {
                Notification::create([
                    'user_id' => $targetUser->id,
                    'title'   => '💬 Balasan Komentar',
                    'message' => auth()->user()->name . ' membalas komentar Anda.',
                    'type'    => 'info_post',
                    'link'    => '/post/' . $this->post->id,
                ]);
            }
        } else {
            // Jika ini komentar baru: Notif ke pembuat postingan
            if ($this->post->user_id !== auth()->id()) {
                Notification::create([
                    'user_id' => $this->post->user_id, 
                    'title'   => '💬 Komentar Baru',
                    'message' => auth()->user()->name . ' mengomentari: "' . Str::limit($commentText, 30) . '"',
                    'type'    => 'info_post',
                    'link'    => '/post/' . $this->post->id,
                ]);
            }
        }

        $this->comment_text = '';
        $this->replyState = null; // Reset status balasan
        $this->showComments = true; 
    }
}; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
    {{-- Header: Profil & Waktu --}}
    <div class="p-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <img src="{{ $post->user->avatar ? asset('storage/' . $post->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($post->user->name) . '&background=random&color=fff' }}"
                    alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm">
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
            </div>

            <div>
                <div class="flex items-center gap-2">
                    <h4 class="font-bold text-gray-900 leading-none">{{ $post->user->name }}</h4>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                        {{ in_array($post->user->role, ['dekan', 'kaprodi', 'admin']) ? 'bg-purple-100 text-purple-600' : 'bg-cyan-100 text-cyan-600' }}">
                        {{ $post->user->role }}
                    </span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">{{ $post->created_at->diffForHumans() }}</p>
            </div>
        </div>

        @if (auth()->id() === $post->user_id || auth()->user()->role === 'dekan' || (auth()->user()->role === 'kaprodi' && $post->user->role !== 'dekan'))
            <button wire:click="$parent.deletePost({{ $post->id }})" wire:confirm="Apakah Anda yakin ingin menghapus postingan ini?" class="text-gray-400 hover:text-red-600 transition p-1 rounded-lg hover:bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Konten Postingan --}}
    <div class="px-4 pb-3 text-gray-800 text-sm leading-relaxed whitespace-pre-line">
        {{ $post->content }}
    </div>

    @if ($post->image)
        <div class="px-4 pb-4">
            <img src="{{ asset('storage/' . $post->image) }}" alt="post" class="rounded-lg w-full object-cover max-h-96">
        </div>
    @endif

    {{-- Action Bar --}}
    <div class="px-4 py-2 border-t border-gray-50 flex items-center space-x-6 text-gray-500 text-sm">
        <button wire:click="toggleLike" class="flex items-center space-x-2 transition hover:scale-105 active:scale-95">
            @if ($isLiked)
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001Z" /></svg>
                <span class="text-red-500 font-semibold">{{ $likesCount }} Suka</span>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                <span>{{ $likesCount }} Suka</span>
            @endif
        </button>

        <button wire:click="toggleComments" class="flex items-center space-x-2 hover:text-blue-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 {{ $showComments ? 'text-blue-600' : '' }}"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785 0.5 0.0 0 0 0 .584.14 0.05 0.05 0 0 0 .471-.334 4.502 4.502 0 0 0 1.223-1.39 1.11 1.11 0 0 1 1.154-.514 8.718 8.718 0 0 0 3.01.54Z" /></svg>
            <span class="{{ $showComments ? 'text-blue-600 font-semibold' : '' }}">{{ $commentsCount }} Komentar</span>
        </button>
    </div>

    {{-- Bagian Komentar Berjenjang --}}
    @if ($showComments)
        <div class="bg-gray-50 border-t border-gray-100 transition-all">
            <div class="p-4 space-y-4 max-h-96 overflow-y-auto">
                @forelse($comments as $comment)
                    <div>
                        {{-- Komentar Level 1 --}}
                        <div class="flex space-x-3">
                            <img src="{{ $comment->user->avatar ? asset('storage/' . $comment->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comment->user->name) . (in_array($comment->user->role, ['dekan', 'kaprodi', 'admin']) ? '&background=7c3aed&color=fff' : '') }}"
                                class="w-9 h-9 rounded-xl object-cover border-2 {{ in_array($comment->user->role, ['dekan', 'kaprodi', 'admin']) ? 'border-purple-200' : 'border-gray-100' }}">
                            
                            <div class="w-full">
                                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-200">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs font-bold text-gray-900">{{ $comment->user->name }}</span>
                                        <span class="text-[10px] text-gray-400">{{ $comment->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-700 leading-snug">{{ $comment->comment_text }}</p>
                                </div>
                                <button wire:click="setReply({{ $comment->id }})" class="text-[10px] font-bold text-gray-400 hover:text-blue-600 mt-1.5 ml-2 uppercase tracking-wide">
                                    Balas
                                </button>
                            </div>
                        </div>

                        {{-- Balasan Level 2 --}}
                        @if($comment->replies->count() > 0)
                            <div class="ml-12 mt-2 space-y-3 border-l-2 border-gray-200 pl-3">
                                @foreach($comment->replies as $reply)
                                    <div class="flex space-x-2">
                                        <img src="{{ $reply->user->avatar ? asset('storage/' . $reply->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name) . (in_array($reply->user->role, ['dekan', 'kaprodi', 'admin']) ? '&background=7c3aed&color=fff' : '') }}"
                                            class="w-7 h-7 rounded-xl object-cover border-2 {{ in_array($reply->user->role, ['dekan', 'kaprodi', 'admin']) ? 'border-purple-200' : 'border-gray-100' }}">
                                        
                                        <div class="w-full">
                                            <div class="bg-white p-2.5 rounded-2xl shadow-sm border border-gray-200">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-[11px] font-bold text-gray-900">{{ $reply->user->name }}</span>
                                                    <span class="text-[9px] text-gray-400">{{ $reply->created_at?->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-[11px] text-gray-700 leading-snug">
                                                    @if(str_starts_with($reply->comment_text, '@'))
                                                        @php $parts = explode(' ', $reply->comment_text, 2); @endphp
                                                        <span class="text-blue-600 font-bold">{{ $parts[0] }}</span> {{ $parts[1] ?? '' }}
                                                    @else
                                                        {{ $reply->comment_text }}
                                                    @endif
                                                </p>
                                            </div>
                                            <button wire:click="setReply({{ $reply->id }})" class="text-[9px] font-bold text-gray-400 hover:text-blue-600 mt-1 ml-2 uppercase tracking-wide">
                                                Balas
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-xs text-gray-400 py-2">Belum ada komentar.</p>
                @endforelse
            </div>

            {{-- Input Tulis Komentar --}}
            <div class="p-4 border-t border-gray-100 bg-white">
                @if($replyState)
                    @php $parentComment = \App\Models\Comment::find($replyState); @endphp
                    <div class="flex justify-between items-center mb-2 px-3 py-1.5 bg-blue-50 rounded-lg border border-blue-100">
                        <span class="text-[10px] text-blue-600 font-bold">Membalas @ {{ $parentComment->user->name ?? '...' }}</span>
                        <button wire:click="cancelReply" class="text-[10px] text-gray-400 hover:text-red-500 font-bold">&times; Batal</button>
                    </div>
                @endif

                <div class="flex items-center space-x-2">
                    <input wire:model="comment_text" wire:keydown.enter="sendComment" type="text"
                        placeholder="{{ $replyState ? 'Tulis balasan Anda...' : 'Tulis komentar...' }}"
                        class="flex-1 bg-gray-100 border-none rounded-full px-4 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    
                    <button wire:click="sendComment" class="text-blue-600 hover:text-blue-800 transition p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                        </svg>
                    </button>
                </div>
                @error('comment_text') <span class="text-[10px] text-red-500 font-medium ml-3 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>
    @endif
</div>