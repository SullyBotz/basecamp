<?php

use App\Models\Post;
use App\Models\OfficialBroadcast;
use App\Models\Notification;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Rules\TanpaKataKasar;

new class extends Component {
    use WithFileUploads;

    public string $content = '';
    public string $title = ''; 
    public $image = null;

    // Helper untuk kirim notifikasi ke semua user (kecuali pengirim)
    private function broadcastNotification($title, $message, $link)
    {
        $now = now();
        // Mengambil semua user ID selain user yang sedang login
        $users = User::where('id', '!=', auth()->id())->get(['id']);
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = [
                'user_id'    => $user->id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'new_post',
                'link'       => $link,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert massal menggunakan chunking agar performa tetap ringan
        foreach (array_chunk($notifications, 500) as $chunk) {
            Notification::insert($chunk);
        }
    }

    public function store()
    {
        $this->content = trim($this->content);
        $this->title = trim($this->title);
        
        $userRole = auth()->user()->role;
        $isOfficial = in_array($userRole, ['dekan', 'kaprodi']);

        // 1. Validasi Input
        $this->validate([
            'content' => ['required', 'string', new TanpaKataKasar()],
            'image'   => ['nullable', 'image', 'max:2048'],
            'title'   => $isOfficial ? ['required', 'string', new TanpaKataKasar()] : ['nullable', 'string'],
        ]);

        // Proses Unggah Gambar
        $imagePath = null;
        if ($this->image) {
            $folder = $isOfficial ? 'broadcasts' : 'posts';
            $imagePath = $this->image->store($folder, 'public');
        }

        // 2. Logika Simpan dan Distribusi Notifikasi Berdasarkan Role
        if ($isOfficial) {
            // JIKA DEKAN / KAPRODI (Pengumuman Resmi)
            $broadcast = OfficialBroadcast::create([
                'admin_id'   => auth()->id(), 
                'title'      => $this->title,
                'content'    => $this->content,
                'image_path' => $imagePath, 
                'is_pinned'  => true,
            ]);

            // Kirim notifikasi ke semua role/user
            $this->broadcastNotification(
                '📢 Pengumuman Resmi', 
                'Ada pengumuman baru dari ' . ucfirst($userRole) . ': ' . $this->title, 
                '/broadcast/' . $broadcast->id
            );

        } else {
            // JIKA MAHASISWA, ALUMNI, ATAU ROLE LAINNYA (Postingan Biasa)
            $post = Post::create([
                'user_id' => auth()->id(),
                'content' => $this->content,
                'image'   => $imagePath, 
            ]);

            // FITUR BARU: Notifikasi hanya terpicu jika role adalah 'alumni' atau 'mahasiswa'
            if (in_array($userRole, ['alumni', 'mahasiswa'])) {
                $roleLabel = $userRole == 'alumni' ? 'Alumni' : 'Mahasiswa';
                
                $this->broadcastNotification(
                    '📝 Postingan Baru ' . $roleLabel, 
                    auth()->user()->name . ' (' . $roleLabel . ') baru saja membagikan kiriman baru.', 
                    '/post/' . $post->id
                );
            }
        }

        // 3. Reset & Refresh Form
        $this->reset(['content', 'title', 'image']);
        $this->dispatch('post-created');
        
        // 💡 4. MAINKAN SUARA SAAT BERHASIL POSTING
        $this->dispatch('play-post-sound');
    }
}; ?>

<div class="mb-6">
    <div class="rounded-3xl border transition-all duration-300 {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'bg-purple-50 border-purple-200 shadow-xl shadow-purple-100/50' : 'bg-white border-gray-100 shadow-sm' }} p-5">

        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <img src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . (in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? '&background=7c3aed&color=fff' : '') }}"
                    class="w-12 h-12 rounded-2xl object-cover border-2 {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'border-purple-200' : 'border-gray-50' }}">
            </div>

            <form wire:submit.prevent="store" class="flex-1 space-y-3" enctype="multipart/form-data">

                @if (in_array(auth()->user()->role, ['dekan', 'kaprodi']))
                    <div class="mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-purple-700 bg-purple-200/50 px-2 py-1 rounded-md mb-2 inline-block">
                            📢 Buat Pengumuman Resmi
                        </span>
                        <input wire:model="title" type="text" placeholder="Masukkan Judul Pengumuman..."
                            class="w-full border-none bg-white rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-purple-500 shadow-sm placeholder:text-gray-400">
                        @error('title') <span class="text-red-500 text-[10px] mt-1 font-bold block pl-1">⚠️ {{ $message }}</span> @enderror
                    </div>
                @endif

                <textarea wire:model="content"
                    placeholder="{{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'Tuliskan isi pengumuman secara detail...' : 'Apa yang ingin Anda bagikan hari ini?' }}"
                    class="w-full border-none {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'bg-white' : 'bg-gray-50' }} rounded-2xl px-4 py-3 text-sm focus:ring-2 {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'focus:ring-purple-500' : 'focus:ring-blue-500' }} transition resize-none min-h-[120px] shadow-inner"></textarea>

                @error('content') <span class="text-red-500 text-[10px] mt-1 font-bold block pl-1">⚠️ {{ $message }}</span> @enderror

                @if ($image)
                    <div class="relative mt-2 inline-block group">
                        <img src="{{ $image->temporaryUrl() }}"
                            class="w-40 h-40 object-cover rounded-2xl shadow-md border-2 {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'border-purple-200' : 'border-blue-100' }}">
                        <button type="button" wire:click="$set('image', null)"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 shadow-lg transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                @endif

                <div class="flex items-center justify-between mt-4 pt-3 border-t {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'border-purple-100' : 'border-gray-50' }}">
                    <div class="flex items-center">
                        <label class="flex items-center space-x-2 {{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'text-purple-600 bg-purple-100' : 'text-gray-500 bg-gray-100' }} px-3 py-1.5 rounded-xl cursor-pointer hover:opacity-80 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Foto</span>
                            <input type="file" wire:model="image" class="hidden" accept="image/*">
                        </label>
                        <div wire:loading wire:target="image" class="ml-3 text-[10px] text-gray-400 italic">Mengunggah gambar...</div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="{{ in_array(auth()->user()->role, ['dekan', 'kaprodi']) ? 'bg-purple-600 hover:bg-purple-700 shadow-purple-200' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-200' }} text-white px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 disabled:opacity-50">
                        <span wire:loading.remove>Kirim</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 💡 ELEMEN AUDIO & SCRIPT UNTUK SUARA NOTIFIKASI BERHASIL POSTING --}}
    <audio id="postNotifAudio" preload="auto">
        <source src="{{ asset('storage/audio/jawa-kobo.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        document.addEventListener('livewire:initialized', () => {
            window.addEventListener('play-post-sound', () => {
                let audio = document.getElementById('postNotifAudio');
                if (audio) {
                    audio.play().catch(function(error) {
                        console.log('Browser mencegah autoplay suara sebelum ada interaksi user.');
                    });
                }
            });
        });
    </script>
</div>