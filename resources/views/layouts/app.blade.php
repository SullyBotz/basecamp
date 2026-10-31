<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Baring - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-grey-50 flex">
        <aside class="w-64 bg-white border-r border-gray-200 fixed h-full hidden lg:block">
            <livewire:layout.sidebar-left />
        </aside>

        <div class="flex-1 lg:ml-64 flex flex-col">
            <header class="h-16 bg-white boder-b border-gray-200 sticky top-0 z-10">
                <livewire:layout.navigation-header />
            </header>

            <main class="p-6 flex flex-col lg:flex-row gap-6">
                <div class="flex-1 space-y-6">
                    {{ $slot }}
                </div>
                <aside class="w-full lg:w-80 space-y-6">
                    <div class="sticky top-24">
                        <livewire:layout.sidebar-right />
                    </div>
                </aside>
            </main>
        </div>
    </div>

</html>
