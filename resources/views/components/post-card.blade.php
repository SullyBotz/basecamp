<div>
    {{-- Close your eyes. Count to one. That is how long forever feels. --}}
    @props(['name', 'time', 'avatar', 'verified' => false])

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 flex items-center justify-between">
            <div class="flew itemx-center space-x-3">
                <img src="{{ $avatar }}" alt="foto-profile" class="w-10 h-10 rounded-full object-cover">
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-gray-900">{{ $name }}</h4>
                        @if ($verified)
                            <span class="bg-cyan-100 text-cyan-600 text-[10px] px-2 py-0.5 rounded-full font-semibold">
                                Alumni Terverifikasi
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500">{{ $time }}</p>
                </div>
            </div>
            <button class="text-gray-400 hover:text-gray-600">...</button>
        </div>

        <div class="px-4 pb-3">
            <p class="text-gray-800 text-sm leading-relaxed">
                {{ $slot }}
            </p>
        </div>

        @if (isset($image))
            <div class="px-4 pb-4">
                <img src="{{ $image }}" alt="post" class="rounded-lg w-full object-cover max-h-96">
            </div>
        @endif

        <div class="px-4 py-3 border-t border-gray-50 flex items-center justify-between text-gray-500 text-sm">
            <div class="flex space-x-6">
                <button class="flex items-center space-x-2 hover:text-blue-600">
                    <span>❤️ 45 Suka</span>
                </button>
                <button class="flex items-center space-x-2 hover:text-blue-600">
                    <span>💬 12 Komentar</span>
                </button>
            </div>
            <button class="hover:text-blue-600">🔗 Bagikan</button>
        </div>
    </div>
</div>
