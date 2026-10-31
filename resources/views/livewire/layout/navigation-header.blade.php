<?php

use App\Models\User;
use App\Models\Job;
use App\Models\Notification;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Actions\Logout;

new class extends Component {
    // Listener agar header otomatis refresh saat profil di-update
    protected $listeners = ['profile-updated' => '$refresh'];

    public string $search = '';
    public bool $showNotifDropdown = false;

    // --- FITUR NOTIFIKASI ---

    // 1. Membaca satu notifikasi dan redirect
    public function markAsRead($id)
    {
        $notif = Notification::find($id);
        if ($notif) {
            $notif->update(['is_read' => true]);
            $this->showNotifDropdown = false;
            
            if ($notif->link) {
                return $this->redirect($notif->link, navigate: true);
            }
        }
    }

    // 2. Menandai semua notifikasi telah dibaca
    public function markAllAsRead()
    {
        Notification::where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    // 3. Menghapus notifikasi
    public function deleteNotification($id)
    {
        Notification::where('id', $id)
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->delete();
    }

    // 4. Mengambil notifikasi secara realtime berdasarkan role
    public function getNotifications()
    {
        $user = auth()->user();
        if (!$user) return collect();

        $role = strtolower($user->role ?? 'mahasiswa');
        $allowedTypes = ['pesan', 'info_post', 'loker_baru', 'new_post', 'info'];

        if (in_array($role, ['dekan', 'kaprodi'])) {
            $allowedTypes = ['loker_tambah', 'registrasi', 'pesan', 'info_post', 'new_post', 'info'];
        }

        return Notification::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->whereIn('type', $allowedTypes)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function with(): array
    {
        $searchStr = trim($this->search);
        $notifications = $this->getNotifications();
        $unreadCount = $notifications->where('is_read', false)->count();
        
        $users = [];
        $lokers = [];
        if (strlen($searchStr) >= 2) {
            $users = User::where('name', 'like', '%' . $searchStr . '%')->limit(3)->get();
            $lokers = Job::where('title', 'like', '%' . $searchStr . '%')
                ->orWhere('company', 'like', '%' . $searchStr . '%')
                ->limit(3)
                ->get();
        }

        return [
            'users' => $users,
            'lokers' => $lokers,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ];
    }

    public function logout(Logout $logout)
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="h-full px-4 flex items-center justify-between bg-white/80 backdrop-blur-md" wire:poll.10s>
    
    <div class="lg:hidden">
        <span class="text-blue-600 font-black text-2xl">B</span>
    </div>

    {{-- KOTAK PENCARIAN MULTI-KATEGORI --}}
    <div class="flex-1 max-w-lg ml-4">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            
            <input wire:model.live="search" type="text"
                class="block w-full pl-10 pr-10 py-2 border-none rounded-xl bg-gray-100 text-xs placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all"
                placeholder="Cari nama alumni, mahasiswa, atau loker...">

            {{-- Tombol silang untuk hapus text pencarian --}}
            @if(strlen($search) > 0)
                <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 font-bold text-sm">
                    &times;
                </button>
            @endif

            {{-- DROPDOWN HASIL PENCARIAN LIVE --}}
            @if (strlen($search) >= 2)
                <div class="absolute left-0 right-0 mt-2 bg-white rounded-2xl border border-gray-100 shadow-xl z-50 overflow-hidden p-2 max-h-96 overflow-y-auto">
                    
                    @if (count($users) === 0 && count($lokers) === 0)
                        <div class="p-4 text-center text-xs text-gray-400 font-medium">
                            Tidak ada hasil untuk "<span class="font-bold text-gray-600">{{ $search }}</span>"
                        </div>
                    @endif

                    {{-- KATEGORI 1: ALUMNI / PENGGUNA --}}
                    @if (count($users) > 0)
                        <div class="px-3 py-1.5 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 mb-1">
                            👥 Alumni & Pengguna
                        </div>
                        
                        @foreach ($users as $user)
                            <a href="/alumni/{{ $user->id }}" wire:navigate 
                               wire:click="$set('search', '')"
                               class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-xl transition group">
                                
                                <img class="h-8 w-8 rounded-full object-cover border border-gray-100"
                                     src="{{ $user->avatar && Storage::disk('public')->exists($user->avatar) ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=0D8ABC&color=fff' }}" 
                                     alt="{{ $user->name }}">
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                                        {{ $user->name }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">
                                        {{ $user->role ?? 'Alumni' }}
                                    </p>
                                </div>
                                <svg class="w-3 h-3 text-gray-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    @endif

                    {{-- KATEGORI 2: LOWONGAN KERJA --}}
                    @if (count($lokers) > 0)
                        <div class="px-3 py-1.5 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 {{ count($users) > 0 ? 'mt-3' : '' }} mb-1">
                            💼 Lowongan Kerja
                        </div>
                        
                        @foreach ($lokers as $loker)
                            <a href="/jobs/{{ $loker->id }}" wire:navigate 
                               wire:click="$set('search', '')"
                               class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-xl transition group">
                                
                                <div class="h-8 w-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                                        {{ $loker->title }}
                                    </p>
                                    <p class="text-[10px] text-gray-500 font-medium truncate">
                                         {{ $loker->company }}
                                    </p>
                                </div>
                                <svg class="w-3 h-3 text-gray-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    @endif

                </div>
            @endif
        </div>
    </div>

    {{-- MENU SISI KANAN (NOTIFIKASI & PROFIL) --}}
    <div class="flex items-center space-x-3">
        
        {{-- 🔔 FITUR REAL-TIME DROPDOWN NOTIFIKASI --}}
        <div class="relative">
            <button wire:click="$set('showNotifDropdown', {{ !$showNotifDropdown }})" class="p-2 text-gray-500 hover:bg-gray-100 rounded-xl relative transition-colors focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                
                {{-- Badge Angka Berwarna Merah (Hanya muncul jika ada notif yang belum dibaca) --}}
                @if($unreadCount > 0)
                    <span class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 bg-red-500 text-white rounded-full border-2 border-white text-[9px] font-bold flex items-center justify-center animate-pulse">
                        {{ $unreadCount }}
                    </span>
                @endif
            </button>

            {{-- Tampilan Dropdown Konten Notifikasi --}}
            @if($showNotifDropdown)
                <div class="absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-gray-100 shadow-2xl z-50 overflow-hidden p-2">
                    
                    {{-- Header Dropdown Notifikasi --}}
                    <div class="px-3 py-2 text-xs font-black text-gray-700 border-b border-gray-50 flex justify-between items-center">
                        <span>🔔 Notifikasi</span>
                        @if($notifications->isNotEmpty())
                            <button wire:click="markAllAsRead" class="text-[9px] text-blue-600 hover:text-blue-800 hover:underline transition-colors focus:outline-none">
                                Tandai semua dibaca
                            </button>
                        @endif
                    </div>

                    {{-- List Notifikasi --}}
                    <div class="max-h-72 overflow-y-auto mt-1 space-y-1">
                        @if($notifications->isEmpty())
                            <div class="p-6 text-center text-xs text-gray-400 font-medium">Belum ada notifikasi baru.</div>
                        @else
                            @foreach($notifications as $notif)
                                <div class="flex gap-2 p-2 rounded-xl transition items-start relative group {{ !$notif->is_read ? 'bg-blue-50/40' : 'hover:bg-gray-50' }}">
                                    
                                    {{-- Area Klik untuk Membaca & Redirect --}}
                                    <button wire:click="markAsRead({{ $notif->id }})" class="flex-1 flex gap-3 text-left focus:outline-none">
                                        
                                        {{-- Custom Icon Berdasarkan Tipe Notifikasi --}}
                                        <div class="h-8 w-8 rounded-xl flex items-center justify-center flex-shrink-0 
                                            {{ $notif->type == 'pesan' ? 'bg-blue-50 text-blue-500' : '' }}
                                            {{ $notif->type == 'info_post' ? 'bg-green-50 text-green-500' : '' }}
                                            {{ in_array($notif->type, ['loker_tambah', 'loker_baru']) ? 'bg-amber-50 text-amber-500' : '' }}">
                                            
                                            @if($notif->type == 'pesan')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            @elseif($notif->type == 'info_post')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0 pr-1 text-left">
                                            <p class="text-xs font-bold text-gray-900 truncate {{ !$notif->is_read ? 'text-blue-900' : '' }}">{{ $notif->title }}</p>
                                            <p class="text-[10px] text-gray-500 line-clamp-2 mt-0.5 leading-relaxed">{{ $notif->message }}</p>
                                            <span class="text-[8px] text-gray-400 font-medium block mt-1">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                    </button>

                                    {{-- Tombol Hapus Notifikasi --}}
                                    <button wire:click="deleteNotification({{ $notif->id }})" 
                                        class="text-gray-300 hover:text-red-500 p-1.5 flex-shrink-0 transition-colors bg-white rounded-full hover:bg-red-50 focus:outline-none border border-transparent hover:border-red-100 opacity-0 group-hover:opacity-100 shadow-sm" 
                                        title="Hapus Notifikasi">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>

                                    {{-- Titik Biru penanda bahwa Notifikasi Belum Dibaca --}}
                                    @if(!$notif->is_read)
                                        <span class="absolute top-2.5 left-2.5 w-2 h-2 bg-blue-500 border border-white rounded-full"></span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                
                {{-- Overlay transparan untuk menutup dropdown jika klik di area luar --}}
                <div wire:click="$set('showNotifDropdown', false)" class="fixed inset-0 z-40 cursor-default"></div>
            @endif
        </div>

        {{-- DROPDOWN PROFIL USER & LOGOUT --}}
        <div class="flex items-center space-x-3 border-l pl-3 ml-1 relative z-50">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-3 focus:outline-none group">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-gray-900 leading-none">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">{{ Auth::user()->role ?? 'Mahasiswa' }}</p>
                        </div>
                        
                        <img class="h-9 w-9 rounded-full object-cover border-2 border-transparent group-hover:border-blue-500 transition-all p-0.5"
                            src="{{ Auth::user()->avatar && Storage::disk('public')->exists(Auth::user()->avatar) ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=0D8ABC&color=fff' }}" 
                            alt="Profile">
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Profile Saya') }}
                    </x-dropdown-link>
                    <hr class="border-gray-100">
                    <button wire:click="logout" class="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 hover:bg-red-50 focus:outline-none transition duration-150">
                        {{ __('Log Out') }}
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>