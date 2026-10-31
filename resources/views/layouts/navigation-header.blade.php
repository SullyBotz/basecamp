<div class="h-full px-6 flex items-center justify-between">
    <div class="lg:hidden">
        <span class="text-blue-600 font-bold text-xl">BC</span>
    </div>

    <div class="flex-1 max-w-md ml-4">
        <div class="relative group">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input type="text" 
                   class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                   placeholder="Cari sesuatu di Basecamp...">
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <button class="p-2 text-gray-400 hover:bg-gray-100 rounded-full relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        
        <div class="flex items-center space-x-2 border-l pl-4">
            <span class="text-sm font-medium text-gray-700 hidden sm:block">{{ Auth::user()->name }}</span>
            <img class="h-9 w-9 rounded-full object-cover border border-blue-500 p-0.5" 
                 src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}" 
                 alt="Profile">
        </div>
    </div>
</div>