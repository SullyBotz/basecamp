@section('title', 'Alumni Direktori')
<?php
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout; 

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with()
    {
        return [
            // 💡 PERBAIKAN: Tambahkan 'prodi' ke dalam eager loading with()
            'alumni' => User::where('role', 'alumni')
                ->with(['profile', 'prodi']) 
                ->where('name', 'like', '%' . $this->search . '%')
                ->paginate(12),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Direktori Alumni</h2>
            <p class="text-sm text-gray-500">Temukan dan terhubung dengan rekan alumni lainnya.</p>
        </div>
        <div class="w-full md:w-72">
            <input wire:model.live="search" type="text" placeholder="Cari nama alumni..."
                class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($alumni as $person)
            {{-- Tambahkan class 'group', 'hover:bg-blue-600', dan 'transition-all' pada container --}}
            <div class="group bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-4 hover:bg-blue-600 hover:border-blue-600 transition-all duration-300">
                
                <img src="{{ $person->avatar ? asset('storage/' . $person->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($person->name) }}"
                    class="w-16 h-16 rounded-full object-cover border-2 border-blue-50 group-hover:border-blue-300 transition-colors duration-300"
                    alt="{{ $person->name }}">
                
                <div class="flex-1">
                    {{-- Nama: Berubah dari abu-abu gelap jadi putih --}}
                    <h4 class="font-bold text-gray-900 group-hover:text-white leading-tight transition-colors duration-300">{{ $person->name }}</h4>
                    
                    {{-- Headline/Prodi: Berubah dari abu-abu jadi biru muda (agar terbaca di background biru tua) --}}
                    <p class="text-xs text-gray-500 group-hover:text-blue-100 mb-1 transition-colors duration-300">
                        {{ $person->headline ?? 'Alumni ' . $person->prodi?->nama }}
                    </p>

                    {{-- Judul Skripsi: Berubah dari biru tua ke biru sangat muda --}}
                    @if ($person->profile?->thesis_title)
                        <p class="text-[10px] text-blue-600 group-hover:text-blue-200 font-medium line-clamp-1 mb-2 italic transition-colors duration-300">
                            "{{ $person->profile->thesis_title }}"
                        </p>
                    @endif

                    {{-- Link Lihat Profil: Berubah jadi putih tebal --}}
                    <a href="{{ route('alumni.show', $person->id) }}" wire:navigate
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 group-hover:text-white group-hover:hover:text-gray-200 hover:underline inline-block transition-colors duration-300">
                        Lihat Profil
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $alumni->links() }}
    </div>
</div>