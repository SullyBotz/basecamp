<?php
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout)
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="flex flex-col h-full py-6 px-4">
    <div class="px-3 mb-10 flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold">B</span>
        </div>
        <span class="text-xl font-bold text-gray-900 tracking-tight">BARING</span>
    </div>

    <nav class="flex-1 space-y-2">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home" wire:navigate>
            <div class="flex items-center gap-3">
                {{-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg> --}}
                <span>Beranda</span>
            </div>
        </x-sidebar-link>

        {{-- Menu Khusus Admin --}}
        @if (auth()->user()->role === 'dekan' || auth()->user()->role === 'kaprodi')
            <div class="pt-6 pb-2">
                <p class="text-[10px] font-bold text-purple-400 uppercase px-3 tracking-widest">Administrator</p>
            </div>

            

            {{-- Verifikasi Loker (Icon Briefcase) --}}
            <a href="/dekan/jobs"
                class="flex items-center gap-3 p-3 rounded-xl transition hover:bg-purple-50 text-gray-600 hover:text-purple-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-medium">Manajemen Karir</span>
            </a>

            <a href="{{ route('admin.users') }}" wire:navigate
                class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('admin.users') ? 'bg-purple-50 text-purple-700 font-bold' : 'hover:bg-purple-50 text-gray-600 hover:text-purple-700' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-sm font-medium">Manajemen Akun</span>
            </a>
        @endif

        @if (auth()->user()->role === 'mahasiswa' || auth()->user()->role === 'alumni')
        <a href="/users" wire:navigate
            class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->is('users') ? 'bg-purple-50 text-purple-700 font-bold' : 'hover:bg-purple-50 text-gray-600 hover:text-purple-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.448a20.477 20.477 0 0 1-2.147-6.174m1.583-11.782c.511.157 1.016.33 1.513.517 1.012.38 1.556 1.487 1.194 2.499a6.003 6.003 0 0 0 0 4.368c.362 1.012-.182 2.118-1.194 2.499a15.415 15.415 0 0 1-1.512.517m0-11.892C12.773 6.5 14.89 6 17.08 6h.336c.75 0 1.36.551 1.44 1.285.096.878.144 1.77.144 2.67 0 .9-.048 1.792-.144 2.67-.08.734-.69 1.285-1.44 1.285h-.336c-2.19 0-4.307-.5-6.14-1.392" />
            </svg>
            <span class="text-sm">Direktori Anggota</span>
        </a>
        @endif

        <x-sidebar-link :href="route('alumni.index')" icon="users">
            <div class="flex items-center gap-3">
                {{-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg> --}}
                <span>Alumni Direktori</span>
            </div>
        </x-sidebar-link>

        <a href="/prodi" wire:navigate
            class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->is('prodi') ? 'bg-purple-50 text-purple-700 font-bold' : 'hover:bg-purple-50 text-gray-600 hover:text-purple-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.448a20.477 20.477 0 0 1-2.147-6.174m1.583-11.782c.511.157 1.016.33 1.513.517 1.012.38 1.556 1.487 1.194 2.499a6.003 6.003 0 0 0 0 4.368c.362 1.012-.182 2.118-1.194 2.499a15.415 15.415 0 0 1-1.512.517m0-11.892C12.773 6.5 14.89 6 17.08 6h.336c.75 0 1.36.551 1.44 1.285.096.878.144 1.77.144 2.67 0 .9-.048 1.792-.144 2.67-.08.734-.69 1.285-1.44 1.285h-.336c-2.19 0-4.307-.5-6.14-1.392" />
            </svg>
            <span class="text-sm">Program Studi</span>
        </a>

        <a href="/announcements" wire:navigate
            class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->is('announcements') ? 'bg-purple-50 text-purple-700 font-bold' : 'hover:bg-purple-50 text-gray-600 hover:text-purple-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.448a20.477 20.477 0 0 1-2.147-6.174m1.583-11.782c.511.157 1.016.33 1.513.517 1.012.38 1.556 1.487 1.194 2.499a6.003 6.003 0 0 0 0 4.368c.362 1.012-.182 2.118-1.194 2.499a15.415 15.415 0 0 1-1.512.517m0-11.892C12.773 6.5 14.89 6 17.08 6h.336c.75 0 1.36.551 1.44 1.285.096.878.144 1.77.144 2.67 0 .9-.048 1.792-.144 2.67-.08.734-.69 1.285-1.44 1.285h-.336c-2.19 0-4.307-.5-6.14-1.392" />
            </svg>
            <span class="text-sm">Pengumuman Fakultas dan Prodi</span>
        </a>

        <x-sidebar-link :href="route('karir.index')" icon="briefcase">
            <div class="flex items-center gap-3">
                {{-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> --}}
                <span>Pusat Karir</span>
            </div>
        </x-sidebar-link>
        <x-sidebar-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" icon="chat" wire:navigate>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <span>Pesan</span>
                </div>
                {{-- Badge Notifikasi --}}
                @php
                    $unreadCount = \App\Models\Message::where('receiver_id', auth()->id())
                        ->where('is_read', false)
                        ->count();
                @endphp
                @if ($unreadCount > 0)
                    <span
                        class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $unreadCount }}</span>
                @endif
            </div>
        </x-sidebar-link>
    </nav>

    <div class="border-t border-gray-100 pt-4">
        <button wire:click="logout"
            class="flex items-center space-x-3 px-4 py-3 text-red-600 hover:bg-red-50 w-full rounded-xl transition-all duration-200 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Logout</span>
        </button>
    </div>
</div>
