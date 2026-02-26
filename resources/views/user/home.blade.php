<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Home</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .fab-pulse {
            animation: fab-pulse-animation 2s infinite;
        }
        @keyframes fab-pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex flex-col items-center justify-center relative overflow-hidden">

    <h1 class="text-3xl font-black text-[#0a1d37] mb-4">Selamat Datang!</h1>
    <p class="text-slate-600 text-lg mb-8">Tekan tombol chat untuk memulai percakapan.</p>

    <!-- Floating Action Button (FAB) -->
    <a href="{{ route('chat.index') }}" 
       class="fixed bottom-8 right-8 w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white shadow-xl hover:bg-red-700 transition-all transform hover:scale-105 fab-pulse">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
    </a>

</body>
</html>
