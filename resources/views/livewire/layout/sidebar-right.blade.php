<?php
use App\Models\User;
use App\Models\Job; // Pastikan model Job sudah ada
use Livewire\Volt\Component;

new class extends Component {
    public function with() {
        return [
            // Mengambil alumni yang memiliki role 'mentor' (atau kriteria lain)
            // Di sini kita ambil 3 user alumni terbaru sebagai contoh mentor
            'mentors' => User::where('role', 'alumni')
                ->latest()
                ->take(3)
                ->get()
                ->map(function($user, $index) {
                    // Menambahkan rank secara statis berdasarkan urutan untuk demo
                    $ranks = ['gold', 'silver', 'bronze'];
                    $user->rank = $ranks[$index] ?? 'bronze';
                    return $user;
                }),

            // Mengambil 3 lowongan kerja terbaru dari database
            'latestJobs' => Job::latest()->take(3)->get()
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-900 flex items-center gap-2 mb-5">
            <span class="text-xl">🏆</span> Top Kontributor Alumni
        </h3>
    
        <div class="space-y-5">
            @forelse($mentors as $mentor)
            <a href="{{ route('alumni.show', $mentor->id) }}" wire:navigate class="flex items-center justify-between group cursor-pointer">
                <div class="flex items-center space-x-3">
                    <img src="{{ $mentor->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($mentor->name) . '&background=random' }}" 
                         class="w-10 h-10 rounded-full border border-gray-100 object-cover">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition">{{ $mentor->name }}</h4>
                        <p class="text-[11px] text-gray-500">{{ $mentor->headline ?? 'Expert Alumni' }}</p>
                    </div>
                </div>
                
                <span class="text-lg">
                    @if($mentor->rank == 'gold') 👑 @elseif($mentor->rank == 'silver') 🥈 @else 🥉 @endif
                </span>
            </a>
            @empty
            <p class="text-xs text-gray-400 text-center italic">Belum ada Alumni.</p>
            @endforelse
        </div>
    
        <a href="{{ route('alumni.index') }}" wire:navigate class="block w-full text-center mt-6 py-2.5 text-xs font-bold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
            Lihat Semua Alumni
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span>💼</span> Loker Terbaru
        </h3>
        <div class="space-y-4">
            @forelse($latestJobs as $job)
            <div class="group cursor-pointer border-t first:border-t-0 border-gray-50 pt-3 first:pt-0">
                <p class="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition">{{ $job->title }}</p>
                <p class="text-[11px] text-gray-500">{{ $job->company }} • {{ $job->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center italic">Belum ada lowongan.</p>
            @endforelse
        </div>
    </div>
</div>