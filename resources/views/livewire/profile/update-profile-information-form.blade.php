@section('title', 'Profile')

<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar;
    public ?string $currentAvatar = '';
    
    // 💡 Properti baru disesuaikan dengan nama field di DB
    public string $role = '';
    public ?string $angkatan = '';
    public ?string $pembimbing_1 = '';
    public ?string $pembimbing_2 = '';
    public ?string $judul_skripsi = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->currentAvatar = $user->avatar;
        
        // 💡 Ambil data akademik dan role dari DB
        $this->role = $user->role ?? ''; 
        $this->angkatan = $user->angkatan ?? '';
        $this->pembimbing_1 = $user->pembimbing_1 ?? '';
        $this->pembimbing_2 = $user->pembimbing_2 ?? '';
        $this->judul_skripsi = $user->judul_skripsi ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // 💡 Validasi dasar
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        // 💡 Validasi tambahan berdasarkan Role
        if ($this->role === 'alumni') {
            $rules['angkatan'] = ['required', 'string', 'max:4'];
            $rules['pembimbing_1'] = ['nullable', 'string', 'max:255'];
            $rules['pembimbing_2'] = ['nullable', 'string', 'max:255'];
            $rules['judul_skripsi'] = ['nullable', 'string'];
        } elseif ($this->role === 'mahasiswa aktif') {
            $rules['angkatan'] = ['required', 'string', 'max:4'];
        }

        $validated = $this->validate($rules);

        // Proses upload jika ada file baru yang dipilih
        if ($this->avatar) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $this->avatar->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->currentAvatar = $user->avatar;
        $this->reset('avatar');

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: RouteServiceProvider::HOME);
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        
        {{-- FITUR FOTO PROFIL --}}
        <div class="flex items-center gap-6 p-4 bg-gray-50 rounded-2xl border border-gray-100">
            <div class="relative w-20 h-20">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="w-full h-full object-cover rounded-full ring-4 ring-indigo-500/20">
                @elseif ($currentAvatar)
                    <img src="{{ asset('storage/' . $currentAvatar) }}" class="w-full h-full object-cover rounded-full ring-2 ring-gray-200">
                @else
                    <div class="w-full h-full bg-indigo-600 text-white rounded-full flex items-center justify-center font-black text-2xl uppercase">
                        {{ substr($name, 0, 1) }}
                    </div>
                @endif

                <div wire:loading wire:target="avatar" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <div class="flex-1 space-y-1">
                <x-input-label for="avatar" :value="__('Foto Profil')" class="!font-bold text-gray-800" />
                <input wire:model="avatar" type="file" id="avatar" accept="image/*"
                    class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <p class="text-[11px] text-gray-400">Format: JPG, PNG, atau WEBP. Maksimal ukuran file 2MB.</p>
                <x-input-error class="mt-1" :messages="$errors->get('avatar')" />
            </div>
        </div>
        {{-- --------------------------------- --}}

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- 💡 INPUT KHUSUS MAHASISWA AKTIF & ALUMNI --}}
        @if (in_array($role, ['mahasiswa aktif', 'alumni']))
            <div>
                <x-input-label for="angkatan" :value="__('Angkatan')" />
                <x-text-input wire:model="angkatan" id="angkatan" type="number" class="mt-1 block w-full" placeholder="Contoh: 2020" required />
                <x-input-error class="mt-2" :messages="$errors->get('angkatan')" />
            </div>
        @endif

        {{-- 💡 INPUT KHUSUS ALUMNI --}}
        @if ($role === 'alumni')
            <div>
                <x-input-label for="pembimbing_1" :value="__('Dosen Pembimbing 1')" />
                <x-text-input wire:model="pembimbing_1" id="pembimbing_1" type="text" class="mt-1 block w-full" placeholder="Nama Dosen Pembimbing 1" />
                <x-input-error class="mt-2" :messages="$errors->get('pembimbing_1')" />
            </div>

            <div>
                <x-input-label for="pembimbing_2" :value="__('Dosen Pembimbing 2')" />
                <x-text-input wire:model="pembimbing_2" id="pembimbing_2" type="text" class="mt-1 block w-full" placeholder="Nama Dosen Pembimbing 2" />
                <x-input-error class="mt-2" :messages="$errors->get('pembimbing_2')" />
            </div>

            <div>
                <x-input-label for="judul_skripsi" :value="__('Judul Skripsi')" />
                <textarea wire:model="judul_skripsi" id="judul_skripsi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" placeholder="Masukkan judul skripsi..."></textarea>
                <x-input-error class="mt-2" :messages="$errors->get('judul_skripsi')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button wire:loading.attr="disabled" wire:target="avatar">
                {{ __('Save') }}
            </x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>