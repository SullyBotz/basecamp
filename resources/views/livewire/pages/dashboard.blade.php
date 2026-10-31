@section('title', 'Dashboard')

<?php
use App\Models\Post;
use App\Models\OfficialBroadcast;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    protected $listeners = ['post-created' => '$refresh'];
    
    public function with()
    {
        return [
            // Mengambil semua pengumuman resmi terbaru dari dekan/kaprodi
            'announcements' => OfficialBroadcast::latest()->get(),
            'posts' => Post::with(['user', 'comments.user', 'likes'])
                ->latest()
                ->get(),
        ];
    }

    public function deletePost($id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return;
        }

        $user = auth()->user();
        $isStructural = in_array($user->role, ['dekan', 'kaprodi']);
        $isOwner = $post->user_id === $user->id;

        if (!$isStructural && !$isOwner) {
            return; 
        }

        $post->delete();
        $this->dispatch('post-created');
    }
}; ?>

<div>
    {{-- STATS CARDS (Hanya untuk Dekan & Kaprodi) --}}
    @if (auth()->user()->role === 'dekan' || auth()->user()->role === 'kaprodi')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-purple-600 to-indigo-700 p-6 rounded-3xl text-white shadow-lg">
                <p class="text-sm opacity-80">Total Alumni</p>
                <h3 class="text-3xl font-black">{{ \App\Models\User::where('role', 'alumni')->count() }}</h3>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Postingan Hari Ini</p>
                <h3 class="text-3xl font-black text-gray-900">
                    {{ \App\Models\Post::whereDate('created_at', now())->count() }}</h3>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Loker Menunggu</p>
                <h3 class="text-3xl font-black text-blue-600">{{ \App\Models\Job::count() }}</h3>
            </div>
        </div>
    @endif

    {{-- Bagian Pengumuman Prodi (SLIDER / CAROUSEL) --}}
    <div class="mb-8">
        <h2 class="text-xs font-black text-purple-600 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span class="w-8 h-[2px] bg-purple-600"></span>
            Pengumuman Terbaru Fakultas dan Prodi
        </h2>

        {{-- Wrapper Utama Carousel Menggunakan Alpine.js --}}
        <div x-data="{ 
                activeSlide: 0, 
                totalSlides: {{ $announcements->count() }},
                next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides },
                prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides },
                startAutoPlay() {
                    setInterval(() => { this.next() }, 5000); {{-- Geser otomatis setiap 5 detik --}}
                }
             }"
             x-init="if (totalSlides > 1) startAutoPlay()"
             class="bg-gray-900 h-64 rounded-[2rem] relative overflow-hidden shadow-xl shadow-purple-100 group">
            
            @forelse ($announcements as $index => $info)
                {{-- Slide Item --}}
                <div x-show="activeSlide === {{ $index }}"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 scale-98"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-98"
                     class="absolute inset-0 h-full w-full">
                     
                    @if ($info->image_path)
                        <img src="{{ asset('storage/' . $info->image_path) }}" class="w-full h-full object-cover opacity-50">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-purple-800 to-indigo-900 opacity-70"></div>
                    @endif

                    <div class="absolute inset-0 p-8 flex flex-col justify-end bg-gradient-to-t from-black/90 via-black/30 to-transparent">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-purple-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Official Info</span>
                            <span class="text-white/60 text-[10px]">{{ $info->created_at->diffForHumans() }}</span>
                        </div>
                        <h2 class="font-black text-2xl text-white leading-tight mb-2 line-clamp-1">
                            {{ $info->title }} {{-- Diubah ke judul asli --}}
                        </h2>
                        <p class="text-sm text-gray-200 line-clamp-2 max-w-xl opacity-90 mb-4">
                            {{ $info->content }}
                        </p>
                        <a href="/announcements" wire:navigate
                            class="w-fit bg-white text-black px-6 py-2 rounded-xl text-xs font-bold hover:bg-purple-50 transition">
                            Baca Selengkapnya
                        </a>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full text-gray-400 gap-2">
                    <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.13 2.13 0 00-1.283-1.283l-6.167-2.147a1.76 1.76 0 01.592-3.417h12.417z"></path>
                    </svg>
                    <p class="italic text-sm">Belum ada pengumuman resmi dari prodi dan fakultas.</p>
                </div>
            @endforelse

            {{-- Navigasi Tombol Manual (Hanya Muncul Jika Pengumuman Lebih Dari 1) --}}
            @if($announcements->count() > 1)
                {{-- Tombol Kiri --}}
                <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 text-white flex items-center justify-center rounded-full backdrop-blur-sm opacity-0 group-hover:opacity-100 transition duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                
                {{-- Tombol Kanan --}}
                <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 text-white flex items-center justify-center rounded-full backdrop-blur-sm opacity-0 group-hover:opacity-100 transition duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                {{-- Indikator Titik (Dots Indicators) --}}
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5">
                    <template x-for="i in totalSlides" :key="i">
                        <button @click="activeSlide = i - 1" 
                                :class="activeSlide === (i - 1) ? 'bg-purple-500 w-6' : 'bg-white/40 w-2'"
                                class="h-2 rounded-full transition-all duration-300 focus:outline-none"></button>
                    </template>
                </div>
            @endif
        </div>
    </div>

    {{-- FEED POSTINGAN UTAMA --}}
    <div class="max-w-2xl mx-auto py-8">
        @livewire('volt.create-post')

        <div class="mt-6 space-y-4">
            @foreach ($posts as $post)
                @livewire('layout.post-card', ['post' => $post], key($post->id))
            @endforeach
        </div>
    </div>
</div>