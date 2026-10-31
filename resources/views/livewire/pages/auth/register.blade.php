@section('title', 'Daftar Akun')

<?php

use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;  
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $whatsapp = ''; 
    public string $prodi_id = ''; 
    public string $role = 'mahasiswa'; // Default awal
    public string $password = '';
    public string $password_confirmation = '';

    // Mengambil data Program Studi dari Database
    public function with(): array
    {
        return [
            'programStudis' => ProgramStudi::where('is_active', true)->orderBy('nama')->get(),
        ];
    }

    public function register(): void
    {
        // Validasi data pendaftaran
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'whatsapp' => ['required', 'string', 'max:20', 'unique:'.User::class], 
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'role'     => ['required', 'in:mahasiswa,alumni'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        $this->validate($rules);

        // 1. Simpan data user baru ke database
        $user = User::create([
            'name'        => $this->name,
            'email'       => $this->email,
            'whatsapp'    => $this->whatsapp,
            'prodi_id'    => $this->prodi_id,
            'role'        => $this->role,
            'password'    => Hash::make($this->password),
            'is_verified' => false, // Wajib nunggu verifikasi kaprodi
            
            // 💡 BYPASS NIM: Penyelamat error jika kolom nim di DB belum dibuat nullable
            'nim'         => 'NIM-' . time(), 
        ]);

        // 2. LOGIKA FILTER: Ambil Kaprodi yang prodinya SAMA dengan pilihan pendaftar
        $kaprodis = User::where('role', 'kaprodi')
                        ->where('prodi_id', $this->prodi_id)
                        ->get();

        // LOG DEBUG 1: Catat ke laravel.log untuk memastikan Kaprodi prodi ini ada di DB atau tidak
        Log::info("Pendaftaran User Baru: Pendaftar memilih Prodi ID [{$this->prodi_id}]. Jumlah Kaprodi ditemukan di DB: " . $kaprodis->count());

        // Ambil nama prodi untuk teks notifikasi
        $namaProdi = ProgramStudi::find($this->prodi_id)->nama ?? 'Program Studi';

        // 3. Jalankan perulangan kirim notif web & WA ke kaprodi terkait
        foreach ($kaprodis as $kaprodi) {
            
            // A. Kirim Notif Sistem (Lonceng Web)
            \App\Models\Notification::create([
                'user_id' => $kaprodi->id,         
                'title'   => 'Pendaftar Baru 🎓',   
                'message' => "User bernama {$user->name} mendaftar sebagai {$user->role} di prodi {$namaProdi} dan menunggu verifikasi Anda.", 
                'type'    => 'registrasi', 
                'is_read' => 0,                  
            ]);

            // B. Kirim Notif WhatsApp via API Fonnte
            if (!empty($kaprodi->whatsapp)) {
                
                // Format text pesan WhatsApp
                $pesanWa = "*PEMBERITAHUAN PENDAFTAR BARU*\n\n";
                $pesanWa .= "Halo Pak/Bu *{$kaprodi->name}*,\n";
                $pesanWa .= "Ada pengguna baru sistem komunitas digital yang memerlukan verifikasi Anda:\n\n";
                $pesanWa .= "👤 *Nama:* {$user->name}\n";
                $pesanWa .= "📧 *Email:* {$user->email}\n";
                $pesanWa .= "🎓 *Status:* " . strtoupper($user->role) . "\n";
                $pesanWa .= "📱 *No. WA Pendaftar:* {$user->whatsapp}\n\n";
                $pesanWa .= "Silakan masuk ke sistem untuk menyetujui atau menolak pendaftaran ini.\n\n";
                $pesanWa .= "_Basecamp Engineering Automated System_";

                $this->sendWhatsAppViaFonnte($kaprodi->whatsapp, $pesanWa);
            } else {
                // LOG DEBUG 2: Jika kaprodinya ketemu tapi kolom nomor HP-nya di DB kosong
                Log::warning("Kaprodi ID [{$kaprodi->id}] ditemukan, tapi nomor WhatsApp di DB kosong.");
            }
        }

        event(new Registered($user));

        session()->flash('success', 'Akun Anda berhasil didaftarkan! Tunggu konfirmasi dari Kaprodi Anda untuk verifikasi status aktif.');

        $this->redirect(route('login'), navigate: true);
    }

    // ========================================================
    // 💡 FUNGSI HELPER UNTUK KIRIM API FONNTE + LOG RESPONSE
    // ========================================================
    private function sendWhatsAppViaFonnte($nomorWa, $pesan)
    {
        // Bersihkan nomor dari spasi, strip, atau simbol + biar murni angka
        $nomorWa = preg_replace('/[^0-9]/', '', $nomorWa);

        try {
            // ⚠️ GANTI DENGAN TOKEN FONNTE ASLI MILIKMU LEK!
            $fonnteToken = 's5JCWdsj7MRWVjFF6uQT'; 

            $response = Http::withHeaders([
                'Authorization' => $fonnteToken
            ])->post('https://api.fonnte.com/send', [
                'target'      => $nomorWa,
                'message'     => $pesan,
                'countryCode' => '62', // Otomatis nambahin 62 di depan jika nomor berawalan 08
            ]);

            // LOG DEBUG 3: Catat hasil balasan asli dari server Fonnte ke laravel.log
            Log::info("Respon Fonnte untuk nomor {$nomorWa}: " . $response->body());

        } catch (\Exception $e) {
            Log::error('Fonnte Gateway Error: ' . $e->getMessage());
        }
    }
}; ?>

<div class="min-h-screen flex flex-col items-center justify-center p-4 bg-gray-50">
    <div class="w-full max-w-lg my-8">
        
        {{-- Card Container --}}
        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-blue-100/50 border border-gray-100 overflow-hidden">
            
            {{-- Header Register --}}
            <div class="p-8 pb-4 text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg shadow-blue-200 -rotate-3">
                    <svg class="w-8 h-8 text-white rotate-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-gray-900">Gabung Sekarang</h2>
                <p class="text-sm text-gray-500 mt-2 font-medium">Lengkapi data diri Anda di bawah ini</p>
            </div>

            <div class="p-8 pt-4">
                <form wire:submit="register" class="space-y-4">
                    
                    {{-- Dropdown Status Akun --}}
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Status Akun</label>
                        <select wire:model.live="role" required
                            class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner font-bold text-blue-600">
                            <option value="mahasiswa">MAHASISWA AKTIF</option>
                            <option value="alumni">ALUMNI</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-1 ml-2 text-[10px]" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Nama Lengkap</label>
                            <input wire:model="name" type="text" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300 font-medium"
                                placeholder="Masukkan nama...">
                            <x-input-error :messages="$errors->get('name')" class="mt-1 ml-2 text-[10px]" />
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Email Aktif</label>
                            <input wire:model="email" type="email" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300 font-medium"
                                placeholder="nama@email.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-1 ml-2 text-[10px]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- WhatsApp --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">No. WhatsApp</label>
                            <input wire:model="whatsapp" type="text" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300 font-medium"
                                placeholder="08123456789">
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-1 ml-2 text-[10px]" />
                        </div>

                        {{-- Program Studi --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Program Studi</label>
                            <select wire:model="prodi_id" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner text-gray-700 font-medium">
                                <option value="">-- Pilih Prodi --</option>
                                @foreach ($programStudis as $prodi)
                                    <option value="{{ $prodi->id }}">{{ $prodi->jenjang }} - {{ $prodi->nama }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('prodi_id')" class="mt-1 ml-2 text-[10px]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Password --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Kata Sandi</label>
                            <input wire:model="password" type="password" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300"
                                placeholder="••••••••">
                            <x-input-error :messages="$errors->get('password')" class="mt-1 ml-2 text-[10px]" />
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Konfirmasi</label>
                            <input wire:model="password_confirmation" type="password" required
                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300"
                                placeholder="••••••••">
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 ml-2 text-[10px]" />
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-4">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full bg-blue-600 text-white py-4 rounded-[1.5rem] text-sm font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 active:scale-[0.98] disabled:opacity-70">
                            <span wire:loading.remove>Daftar Sekarang</span>
                            <span wire:loading>Mendaftarkan...</span>
                        </button>
                    </div>
                </form>

                {{-- Login Link --}}
                <div class="mt-8 text-center">
                    <p class="text-xs font-bold text-gray-400">
                        Sudah punya akun? 
                        <a href="{{ route('login') }}" class="text-blue-600 hover:underline ml-1 uppercase tracking-tighter">Masuk ke Akun</a>
                    </p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center mt-6 text-[10px] font-black text-gray-300 uppercase tracking-[0.2em]">
            &copy; {{ date('Y') }} Basecamp Engineering
        </p>
    </div>
</div>