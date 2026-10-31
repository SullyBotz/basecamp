@section('title', 'Login')



<?php

use App\Livewire\Forms\LoginForm;

use App\Providers\RouteServiceProvider;

use Illuminate\Support\Facades\Session;

use Livewire\Attributes\Layout;

use Livewire\Volt\Component;



new #[Layout('layouts.guest')] class extends Component {

    public LoginForm $form;



    public function login(): void

    {

       

        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);

    }

   

}; ?>







<div class="min-h-screen flex flex-col items-center justify-center">

    {{-- Tangkap dan tampilkan pesan sukses dari halaman pendaftaran --}}

        @if (session()->has('success'))

            <div class="mb-6 px-5 py-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-semibold text-center shadow-sm">

                {{ session('success') }}

            </div>

        @endif

    <div class="w-full max-w-md">

        {{-- Card Container --}}

        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-blue-100/50 border border-gray-100 overflow-hidden">



            {{-- Header Login --}}

            <div class="p-8 pb-4 text-center">

                {{-- Bagian Logo / Foto --}}
    <div class="mx-auto mb-6 flex items-center justify-center">
        {{-- Pastikan path asset() sesuai dengan lokasi dan nama gambar Anda di folder public --}}
        <img src="{{ asset('images/logo.png') }}" 
             alt="Logo Basecamp Engineering" 
             class="w-24 h-24 object-contain drop-shadow-lg">
    </div>

                <h2 class="text-2xl font-black text-gray-900">Selamat Datang di Basecamp Engineering</h2>

                <p class="text-sm text-gray-500 mt-2 font-medium">Silakan masuk ke akun Anda</p>

            </div>



            <div class="p-8 pt-4">

                {{-- Session Status --}}

                <x-auth-session-status

                    class="mb-4 text-center font-bold text-green-600 bg-green-50 p-3 rounded-2xl text-xs"

                    :status="session('status')" />



                <form wire:submit="login" class="space-y-5">

                    {{-- Email Address --}}

                    <div>

                        <label class="text-xs font-black uppercase tracking-widest text-gray-400 ml-1 mb-2 block">Email

                            Anda</label>

                        <div class="relative">

                            <input wire:model="form.email" type="email" required autofocus

                                class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300"

                                placeholder="nama@email.com">

                        </div>

                        <x-input-error :messages="$errors->get('form.email')" class="mt-2 ml-2 text-[10px] font-bold" />

                    </div>



                    {{-- Password --}}

                    <div>

                        <div class="flex justify-between items-center ml-1 mb-2">

                            <label class="text-xs font-black uppercase tracking-widest text-gray-400 block">Kata

                                Sandi</label>

                            @if (Route::has('password.request'))

                                <a class="text-[10px] font-black uppercase text-blue-600 hover:text-blue-700 transition"

                                    href="{{ route('password.request') }}">

                                    Lupa Sandi?

                                </a>

                            @endif

                        </div>

                        <input wire:model="form.password" type="password" required

                            class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 transition shadow-inner placeholder:text-gray-300"

                            placeholder="••••••••">

                        <x-input-error :messages="$errors->get('form.password')" class="mt-2 ml-2 text-[10px] font-bold" />

                    </div>



                    {{-- Remember Me --}}

                    <div class="flex items-center ml-1">

                        <label for="remember" class="inline-flex items-center cursor-pointer group">

                            <div class="relative">

                                <input wire:model="form.remember" id="remember" type="checkbox" class="sr-only peer">

                                <div

                                    class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-all after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5">

                                </div>

                            </div>

                            <span

                                class="ms-3 text-xs font-bold text-gray-500 group-hover:text-gray-700 transition">{{ __('Ingat saya') }}</span>

                        </label>

                    </div>



                    {{-- Submit Button --}}

                    <div class="pt-2">

                        <button type="submit" wire:loading.attr="disabled"

                            class="w-full bg-blue-600 text-white py-4 rounded-[1.5rem] text-sm font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 active:scale-[0.98] disabled:opacity-70">

                            <span wire:loading.remove>Masuk Sekarang</span>

                            <span wire:loading>Menghubungkan...</span>

                        </button>

                    </div>

                </form>



                {{-- Register Link --}}

                <div class="mt-8 text-center">

                    <p class="text-xs font-bold text-gray-400">

                        Belum punya akun?

                        <a href="{{ route('register') }}"

                            class="text-blue-600 hover:underline ml-1 uppercase tracking-tighter">Daftar Akun</a>

                    </p>

                </div>

            </div>

        </div>



        {{-- Footer --}}

        <p class="text-center mt-8 text-[10px] font-black text-gray-300 uppercase tracking-[0.2em]">

            &copy; {{ date('Y') }} Basecamp Engineering

        </p>

    </div>

</div> 

