@section('title', 'Pesan & Diskusi Grup')
<?php
use App\Models\User;
use App\Models\Message;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use App\Rules\TanpaKataKasar;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    #[Url(as: 'user')]
    public $userId = null;

    #[Url(as: 'group')]
    public $groupId = null;

    public $selectedUser = null;
    public $selectedGroup = null;
    public $messageBody = '';
    public $attachment = null;
    public $lastMessageCount = 0;

    // Modal Cari / Pesan Baru
    public $showSearchModal = false;
    public $searchQuery = '';

    // Modal Buat Grup
    public $showCreateGroupModal = false;
    public $groupName = '';
    public $groupDescription = '';

    // Modal Pengaturan & Anggota Grup
    public $showGroupSettingsModal = false;
    public $groupSearchUserQuery = '';

    public function mount()
    {
        if ($this->userId) {
            $this->selectedUser = User::find($this->userId);
        } elseif ($this->groupId) {
            $this->selectedGroup = ChatGroup::with('members')->find($this->groupId);
        }
    }

    public function with()
    {
        $myId = auth()->id();

        // Daftar chat privat
        $recentChatUsers = User::whereHas('sentMessages', function ($q) use ($myId) {
            $q->where('receiver_id', $myId)->whereNull('group_id');
        })->orWhereHas('receivedMessages', function ($q) use ($myId) {
            $q->where('sender_id', $myId)->whereNull('group_id');
        })->where('id', '!=', $myId)->get();

        if ($this->selectedUser && !$recentChatUsers->contains('id', $this->selectedUser->id)) {
            $recentChatUsers->prepend($this->selectedUser);
        }

        // Daftar grup tempat user bergabung
        $myGroups = ChatGroup::whereHas('members', function ($q) use ($myId) {
            $q->where('user_id', $myId);
        })->latest()->get();

        // Pencarian user untuk chat privat atau tambah anggota grup
        $searchResults = [];
        if (strlen($this->searchQuery) > 2) {
            $searchResults = User::where('id', '!=', $myId)
                ->where('name', 'like', '%' . $this->searchQuery . '%')
                ->take(5)->get();
        }

        // Pencarian user untuk ditambahkan ke dalam grup
        $groupAddSearchResults = [];
        if ($this->selectedGroup && strlen($this->groupSearchUserQuery) > 1) {
            $existingMemberIds = $this->selectedGroup->members->pluck('id')->toArray();
            $groupAddSearchResults = User::whereNotIn('id', $existingMemberIds)
                ->where('name', 'like', '%' . $this->groupSearchUserQuery . '%')
                ->take(5)->get();
        }

        return [
            'recentChats'           => $recentChatUsers,
            'myGroups'              => $myGroups,
            'chatMessages'          => $this->selectedUser ? $this->getDirectMessages() : ($this->selectedGroup ? $this->getGroupMessages() : []),
            'searchResults'         => $searchResults,
            'groupAddSearchResults' => $groupAddSearchResults,
        ];
    }

    public function getDirectMessages()
    {
        $messages = Message::whereNull('group_id')
            ->where(function ($q) {
                $q->where('sender_id', auth()->id())->where('receiver_id', $this->selectedUser->id);
            })->orWhere(function ($q) {
                $q->where('sender_id', $this->selectedUser->id)->where('receiver_id', auth()->id());
            })->oldest()->get();

        $this->checkSoundNotification($messages);
        return $messages;
    }

    public function getGroupMessages()
    {
        $messages = Message::where('group_id', $this->selectedGroup->id)->oldest()->get();
        $this->checkSoundNotification($messages);
        return $messages;
    }

    public function checkSoundNotification($messages)
    {
        $currentCount = $messages->count();
        if ($this->lastMessageCount > 0 && $currentCount > $this->lastMessageCount) {
            $lastMessage = $messages->last();
            if ($lastMessage->sender_id !== auth()->id()) {
                $this->dispatch('play-message-sound');
                $this->dispatch('scroll-to-bottom');
            }
        }
        $this->lastMessageCount = $currentCount;
    }

    public function selectUser(User $user)
    {
        $this->selectedUser = $user;
        $this->selectedGroup = null;
        $this->userId = $user->id;
        $this->groupId = null;
        $this->resetChatState();
    }

    public function selectGroup(ChatGroup $group)
    {
        $this->selectedGroup = $group->load('members');
        $this->selectedUser = null;
        $this->groupId = $group->id;
        $this->userId = null;
        $this->resetChatState();
    }

    public function resetChatState()
    {
        $this->showSearchModal = false;
        $this->searchQuery = '';
        $this->attachment = null;
        $this->lastMessageCount = 0;
        $this->dispatch('scroll-to-bottom');
    }

    // 🌟 FITUR BARU: Keluar dari grup
    public function leaveGroup()
    {
        if (!$this->selectedGroup) return;

        $myId = auth()->id();

        // 1. Jangan izinkan pembuat asli (creator) keluar begitu saja jika masih ada anggota lain
        // (Atau bisa dihapus validasi ini jika creator bebas keluar kapan saja)
        if ($myId === $this->selectedGroup->creator_id) {
            $memberCount = $this->selectedGroup->members()->count();
            if ($memberCount > 1) {
                $this->dispatch('swal:error', title: 'Peringatan!', text: 'Pembuat grup tidak dapat keluar sebelum menunjuk admin lain atau mengeluarkan semua anggota.');
                return;
            }
        }

        // 2. Hapus relasi user dari tabel chat_group_members
        ChatGroupMember::where('chat_group_id', $this->selectedGroup->id)
            ->where('user_id', $myId)
            ->delete();

        // 3. Jika setelah keluar member grup jadi 0, hapus grupnya beserta pesan-pesannya (opsional agar database tetap bersih)
        if ($this->selectedGroup->members()->count() === 0) {
            $this->selectedGroup->messages()->delete();
            $this->selectedGroup->delete();
        }

        // 4. Reset pilihan grup ke null
        $this->selectedGroup = null;
        $this->groupId = null;
        $this->dispatch('swal:success', title: 'Berhasil', text: 'Anda telah keluar dari grup.');
    }

    // ================= FITUR GRUP CHAT =================
    public function createGroup()
    {
        $this->validate([
            'groupName'        => 'required|string|max:255',
            'groupDescription' => 'nullable|string|max:500',
        ]);

        $group = ChatGroup::create([
            'name'        => $this->groupName,
            'description' => $this->groupDescription,
            'creator_id'  => auth()->id(),
        ]);

        // Masukkan pembuat sebagai admin grup
        ChatGroupMember::create([
            'chat_group_id' => $group->id,
            'user_id'       => auth()->id(),
            'is_admin'      => true,
        ]);

        $this->reset(['groupName', 'groupDescription', 'showCreateGroupModal']);
        $this->selectGroup($group);
        $this->dispatch('swal:success', title: 'Berhasil!', text: 'Grup chat berhasil dibuat.');
    }

    public function addMemberToGroup($userId)
    {
        if (!$this->selectedGroup) return;

        // Cek apakah yang menambah adalah admin/pembuat
        if (!$this->isCurrentUserGroupAdmin()) {
            return;
        }

        ChatGroupMember::firstOrCreate([
            'chat_group_id' => $this->selectedGroup->id,
            'user_id'       => $userId,
        ], [
            'is_admin' => false,
        ]);

        $this->selectedGroup->load('members');
        $this->groupSearchUserQuery = '';
    }

    public function removeMemberFromGroup($userId)
    {
        if (!$this->selectedGroup) return;
        if (!$this->isCurrentUserGroupAdmin()) return;

        // Jangan izinkan mengeluarkan creator grup
        if ($userId === $this->selectedGroup->creator_id) return;

        ChatGroupMember::where('chat_group_id', $this->selectedGroup->id)
            ->where('user_id', $userId)
            ->delete();

        $this->selectedGroup->load('members');
    }

    public function toggleAdminStatus($userId)
    {
        if (!$this->selectedGroup) return;
        // Hanya pembuat asli yang bisa mengubah status admin member lain
        if (auth()->id() !== $this->selectedGroup->creator_id) return;

        $member = ChatGroupMember::where('chat_group_id', $this->selectedGroup->id)
            ->where('user_id', $userId)->first();

        if ($member && $userId !== $this->selectedGroup->creator_id) {
            $member->update(['is_admin' => !$member->is_admin]);
            $this->selectedGroup->load('members');
        }
    }

    public function isCurrentUserGroupAdmin()
    {
        if (!$this->selectedGroup) return false;
        if (auth()->id() === $this->selectedGroup->creator_id) return true;

        return $this->selectedGroup->members()
            ->where('user_id', auth()->id())
            ->where('is_admin', true)
            ->exists();
    }

    // ================= KIRIM & HAPUS PESAN =================
    public function sendMessage()
    {
        if (!$this->selectedUser && !$this->selectedGroup) return;

        $this->validate([
            'messageBody' => ['nullable', 'string', new \App\Rules\TanpaKataKasar()],
            'attachment'  => ['nullable', 'file', 'max:40960', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar'],
        ]);

        if (empty(trim($this->messageBody)) && !$this->attachment) return;

        $attachmentPath = $this->attachment ? $this->attachment->store('messages-attachments', 'public') : null;

        if ($this->selectedUser) {
            Message::create([
                'sender_id'   => auth()->id(),
                'receiver_id' => $this->selectedUser->id,
                'body'        => $this->messageBody ?? '',
                'attachment'  => $attachmentPath,
            ]);

            Notification::create([
                'user_id' => $this->selectedUser->id,
                'title'   => 'Pesan Baru 💬',
                'message' => auth()->user()->name . ' mengirimkan pesan.',
                'type'    => 'pesan',
                'link'    => '/pesan?user=' . auth()->id(),
            ]);
        } elseif ($this->selectedGroup) {
            Message::create([
                'sender_id' => auth()->id(),
                'group_id'  => $this->selectedGroup->id,
                'body'      => $this->messageBody ?? '',
                'attachment'=> $attachmentPath,
            ]);
        }

        $this->reset(['messageBody', 'attachment']);
        $this->dispatch('scroll-to-bottom');
    }

    public function deleteMessage($messageId)
    {
        $message = Message::find($messageId);
        if (!$message) return;

        $canDelete = false;

        // Jika pesan privat (milik sendiri)
        if ($message->sender_id === auth()->id()) {
            $canDelete = true;
        }

        // Jika pesan grup dan user adalah Admin Grup
        if ($message->group_id) {
            $group = ChatGroup::find($message->group_id);
            if ($group && ($group->creator_id === auth()->id() || $group->members()->where('user_id', auth()->id())->where('is_admin', true)->exists())) {
                $canDelete = true;
            }
        }

        if ($canDelete) {
            if ($message->attachment) {
                Storage::disk('public')->delete($message->attachment);
            }
            $message->delete();
        }
    }

    public function deleteChat()
    {
        if (!$this->selectedUser) return;
        $myId = auth()->id();
        $otherId = $this->selectedUser->id;

        $messages = Message::whereNull('group_id')->where(function ($q) use ($myId, $otherId) {
            $q->where('sender_id', $myId)->where('receiver_id', $otherId);
        })->orWhere(function ($q) use ($myId, $otherId) {
            $q->where('sender_id', $otherId)->where('receiver_id', $myId);
        })->get();

        foreach ($messages as $msg) {
            if ($msg->attachment) Storage::disk('public')->delete($msg->attachment);
            $msg->delete();
        }

        $this->selectedUser = null;
        $this->userId = null;
    }
}; ?>

<div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex h-[calc(100vh-120px)]">
        
        {{-- SIDEBAR KIRI (Daftar Chat & Grup) --}}
        <div class="w-1/3 border-r border-gray-100 flex flex-col">
            <div class="p-4 border-b border-gray-50 flex items-center justify-between">
                <h2 class="font-bold text-lg text-blue-600 italic">Messages & Groups</h2>
                <div class="flex items-center gap-1">
                    {{-- Tombol Buat Grup --}}
                    <button wire:click="$set('showCreateGroupModal', true)" class="p-2 hover:bg-purple-50 rounded-full text-purple-600 transition" title="Buat Grup Baru">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </button>
                    {{-- Tombol Chat Privat Baru --}}
                    <button wire:click="$set('showSearchModal', true)" class="p-2 hover:bg-blue-50 rounded-full text-blue-600 transition" title="Chat Privat Baru">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-50">
                {{-- KELOMPOK GRUP CHAT --}}
                @if($myGroups->count() > 0)
                    <div class="p-3 bg-gray-50/50">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Grup Diskusi</p>
                        <div class="space-y-1">
                            @foreach ($myGroups as $group)
                                <button wire:click="selectGroup({{ $group->id }})"
                                    class="w-full p-2.5 rounded-xl flex items-center gap-3 hover:bg-white transition {{ $selectedGroup?->id == $group->id ? 'bg-purple-100/60 shadow-sm' : '' }}">
                                    <div class="w-10 h-10 bg-purple-600 text-white rounded-xl flex items-center justify-center font-bold shrink-0">
                                        {{ strtoupper(substr($group->name, 0, 2)) }}
                                    </div>
                                    <div class="text-left truncate">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ $group->name }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $group->members->count() }} Anggota</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- KELOMPOK CHAT PRIVAT --}}
                <div class="p-3">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Pesan Privat</p>
                    <div class="space-y-1">
                        @forelse ($recentChats as $chatUser)
                            <button wire:click="selectUser({{ $chatUser->id }})"
                                class="w-full p-2.5 rounded-xl flex items-center gap-3 hover:bg-gray-50 transition {{ $selectedUser?->id == $chatUser->id ? 'bg-blue-50' : '' }}">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($chatUser->name) }}" class="w-10 h-10 rounded-full shrink-0">
                                <div class="text-left truncate">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ $chatUser->name }}</p>
                                    <p class="text-[11px] text-gray-500 capitalize">{{ $chatUser->role }}</p>
                                </div>
                            </button>
                        @empty
                            <p class="text-center text-xs text-gray-400 py-4 italic">Belum ada percakapan privat.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- AREA UTAMA CHAT --}}
        <div class="flex-1 flex flex-col bg-gray-50/30">
            @if ($selectedUser || $selectedGroup)
                {{-- HEADER CHAT --}}
                <div class="p-4 bg-white border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($selectedUser)
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($selectedUser->name) }}" class="w-10 h-10 rounded-full">
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">{{ $selectedUser->name }}</h3>
                                <p class="text-[10px] text-gray-500 capitalize">{{ $selectedUser->role }}</p>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-purple-600 text-white rounded-xl flex items-center justify-center font-bold">
                                {{ strtoupper(substr($selectedGroup->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">{{ $selectedGroup->name }}</h3>
                                <p class="text-[10px] text-purple-600 font-semibold">{{ $selectedGroup->members->count() }} Anggota &bull; Klik info untuk kelola</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if($selectedGroup)
                            {{-- Tombol Buka Modal Pengaturan Grup --}}
                            <button wire:click="$set('showGroupSettingsModal', true)" class="px-3 py-1.5 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-xl font-bold text-xs transition">
                                Kelola Grup
                            </button>
                        @else
                            <button wire:click="deleteChat" wire:confirm="Hapus seluruh riwayat chat?" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition" title="Hapus Chat">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ISI PESAN --}}
                <div id="chat-window" class="flex-1 overflow-y-auto p-4 space-y-3" wire:poll.5s>
                    @foreach ($chatMessages as $msg)
                        @php
                            $isMe = $msg->sender_id == auth()->id();
                            $sender = User::find($msg->sender_id);
                        @endphp
                        <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-tl-none' }}">
                                
                                {{-- Nama Pengirim jika di Grup dan bukan pesan kita --}}
                                @if($selectedGroup && !$isMe)
                                    <p class="text-[11px] font-bold text-purple-600 mb-1">{{ $sender?->name ?? 'Anggota' }}</p>
                                @endif

                                @if ($msg->attachment)
                                    @php
                                        $ext = strtolower(pathinfo($msg->attachment, PATHINFO_EXTENSION));
                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    @if ($isImage)
                                        <div class="mb-2 overflow-hidden rounded-xl">
                                            <a href="{{ asset('storage/' . $msg->attachment) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $msg->attachment) }}" class="max-h-60 w-full object-cover rounded-xl shadow-sm">
                                            </a>
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <a href="{{ asset('storage/' . $msg->attachment) }}" target="_blank" class="flex items-center gap-2 p-2 rounded-xl text-xs border bg-white/10">
                                                <span class="truncate font-medium">{{ basename($msg->attachment) }}</span>
                                            </a>
                                        </div>
                                    @endif
                                @endif

                                @if (!empty(trim($msg->body)))
                                    <p class="whitespace-pre-line leading-relaxed">{{ $msg->body }}</p>
                                @endif

                                <div class="mt-1 flex items-center justify-between gap-4">
                                    {{-- Tombol Hapus Pesan (Bisa dihapus pengirim atau Admin Grup) --}}
                                    @php
                                        $canDelete = $isMe || ($selectedGroup && $this->isCurrentUserGroupAdmin());
                                    @endphp
                                    @if($canDelete)
                                        <button wire:click="deleteMessage({{ $msg->id }})" wire:confirm="Hapus pesan ini?" class="text-white/70 hover:text-red-300 transition text-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @else
                                        <div></div>
                                    @endif

                                    <p class="text-[9px] opacity-70">{{ $msg->created_at->timezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- FORM INPUT PESAN --}}
                <div class="p-4 bg-white border-t border-gray-100">
                    @if ($attachment)
                        <div class="mb-3 p-2.5 bg-gray-50 rounded-2xl flex items-center justify-between text-xs text-gray-600 border">
                            <span class="truncate font-medium">Berkas dipilih</span>
                            <button type="button" wire:click="$set('attachment', null)" class="text-red-500 font-bold">&times;</button>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <label class="cursor-pointer p-2 hover:bg-gray-100 rounded-full text-gray-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <input type="file" wire:model="attachment" class="hidden">
                        </label>
                        <input wire:model="messageBody" wire:keydown.enter="sendMessage" type="text" placeholder="Kirim pesan..." class="flex-1 border-none bg-gray-100 rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <button wire:click="sendMessage" class="text-blue-600 font-bold px-4 py-2 hover:bg-blue-50 rounded-full transition">Kirim</button>
                    </div>
                    {{-- 💡 PASTIKAN BAGIAN INI ADA AGAR PESAN PERINGATAN MUNCUL --}}
                    @error('messageBody') 
                        <span class="text-xs text-red-500 mt-2 block pl-3 font-semibold">⚠️ {{ $message }}</span> 
                    @enderror
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
                    <p class="font-bold text-gray-900">Pilih Percakapan atau Grup</p>
                    <p class="text-sm">Mulai berdiskusi dengan mahasiswa, alumni, atau buat grup fakultas.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL BUAT GRUP --}}
    @if($showCreateGroupModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6 space-y-4">
                <h3 class="font-black text-gray-900 text-lg">Buat Grup Diskusi Baru</h3>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Grup</label>
                    <input wire:model="groupName" type="text" placeholder="Contoh: Pejuang Skripsi Teknik 2026" class="w-full bg-gray-100 rounded-xl px-4 py-2 text-sm border-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Deskripsi (Opsional)</label>
                    <textarea wire:model="groupDescription" rows="2" placeholder="Tujuan grup..." class="w-full bg-gray-100 rounded-xl px-4 py-2 text-sm border-none"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button wire:click="$set('showCreateGroupModal', false)" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs">Batal</button>
                    <button wire:click="createGroup" class="flex-1 px-4 py-2.5 bg-purple-600 text-white rounded-xl font-bold text-xs">Buat Grup</button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PENGATURAN & TAMBAH ANGGOTA GRUP --}}
    @if($showGroupSettingsModal && $selectedGroup)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                <div class="p-6 border-b flex justify-between items-center bg-purple-600 text-white">
                    <h3 class="font-black">Kelola Anggota: {{ $selectedGroup->name }}</h3>
                    <button wire:click="$set('showGroupSettingsModal', false)" class="text-white text-xl">&times;</button>
                </div>
                
                
                <div class="p-6 space-y-6 overflow-y-auto">
                    {{-- HANYA ADMIN YANG BISA MENAMBAH ANGGOTA --}}
                    @if($this->isCurrentUserGroupAdmin())
                        <div class="bg-purple-50 p-4 rounded-2xl space-y-3">
                            <p class="text-xs font-bold text-purple-700 uppercase">Tambah Anggota Baru</p>
                            <input wire:model.live="groupSearchUserQuery" type="text" placeholder="Cari nama user..." class="w-full bg-white rounded-xl px-4 py-2 text-sm border-gray-200">
                            
                            @if(strlen($groupSearchUserQuery) > 1)
                                <div class="bg-white rounded-xl shadow border divide-y">
                                    @forelse($groupAddSearchResults as $userToAdd)
                                        <div class="p-2.5 flex items-center justify-between">
                                            <span class="text-sm font-semibold">{{ $userToAdd->name }}</span>
                                            <button wire:click="addMemberToGroup({{ $userToAdd->id }})" class="px-3 py-1 bg-purple-600 text-white rounded-lg text-xs font-bold">Tambah</button>
                                        </div>
                                    @empty
                                        <p class="p-3 text-xs text-gray-400 text-center">User tidak ditemukan.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase mb-3">Daftar Anggota ({{ $selectedGroup->members->count() }})</p>
                        <div class="space-y-2">
    @foreach($selectedGroup->members as $member)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <div class="flex items-center gap-3">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}" class="w-8 h-8 rounded-full">
                <div>
                    <p class="text-sm font-bold text-gray-900">
                        {{ $member->name }} 
                        {{-- Indikator "(Anda)" jika itu akun yang sedang login --}}
                        @if($member->id === auth()->id())
                            <span class="text-xs text-blue-600 font-normal">(Anda)</span>
                        @endif
                    </p>
                    <p class="text-[10px] text-purple-600 font-bold">
                        {{ $member->id === $selectedGroup->creator_id ? 'Pembuat Grup' : ($member->pivot->is_admin ? 'Admin' : 'Anggota') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                {{-- 🌟 JIKA INI AKUN KITA SENDIRI, MUNCULKAN TOMBOL KELUAR DI SINI --}}
                @if($member->id === auth()->id())
                    <button wire:click="leaveGroup" 
                        wire:confirm="Apakah Anda yakin ingin keluar dari grup ini?"
                        class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-600 text-xs font-bold rounded-lg transition">
                        Keluar Grup
                    </button>
                @endif

                {{-- Opsi Admin untuk member lain --}}
                @if($this->isCurrentUserGroupAdmin() && auth()->id() !== $member->id && $member->id !== $selectedGroup->creator_id)
                    <button wire:click="toggleAdminStatus({{ $member->id }})" class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-lg hover:bg-blue-100 transition">
                        {{ $member->pivot->is_admin ? 'Turunkan' : 'Jadikan Admin' }}
                    </button>
                    <button wire:click="removeMemberFromGroup({{ $member->id }})" class="px-2 py-1 bg-red-50 text-red-600 text-[10px] font-bold rounded-lg hover:bg-red-100 transition">Keluarkan</button>
                @endif
            </div>
        </div>
    @endforeach
</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- AUDIO & SCRIPT --}}
    <audio id="messageAudio" preload="auto">
        <source src="{{ asset('storage/audio/jawa-kobo.mp3') }}" type="audio/mpeg">
    </audio>
    <script>
        function scrollDown() {
            const chatWindow = document.getElementById('chat-window');
            if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
        }
        window.addEventListener('scroll-to-bottom', scrollDown);
        document.addEventListener('livewire:initialized', () => {
            const notifSound = new Audio("{{ asset('storage/audio/jawa-kobo.mp3') }}");
            window.addEventListener('play-message-sound', () => {
                notifSound.currentTime = 0;
                notifSound.play().catch(e => console.log(e));
            });
        });
    </script>
</div>