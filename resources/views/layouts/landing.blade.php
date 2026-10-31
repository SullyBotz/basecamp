<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Basecamp Engineering - Fakultas Teknik Universitas Pahlawan</title>
    
    {{-- Fonts & Vite Assets --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,750,800,900&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-gray-900 antialiased m-0 p-0 selection:bg-blue-600 selection:text-white">
    {{ $slot }}
</body>
</html>