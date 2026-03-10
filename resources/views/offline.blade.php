<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оффлайн | CatVRF 2026</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@900&display=swap');
    </style>
</head>
<body class="bg-[#050510] flex items-center justify-center min-h-screen p-6 overflow-hidden">
    <div class="fixed top-0 left-0 w-full h-full opacity-10 blur-3xl pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-600 rounded-full"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-900 rounded-full"></div>
    </div>

    <div class="text-center space-y-8 relative z-10 transition-all duration-1000 animate-pulse">
        <div class="w-24 h-24 bg-gradient-to-br from-blue-600 to-indigo-900 rounded-3xl mx-auto flex items-center justify-center shadow-2xl shadow-blue-500/20">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0-12.728L5.636 18.364m12.728-12.728L5.636 5.636m12.728 12.728L18.364 18.364"/></svg>
        </div>
        
        <h1 class="text-4xl md:text-6xl font-black text-white uppercase tracking-tighter italic">Offline Mode</h1>
        <p class="text-gray-400 text-sm font-bold opacity-60 uppercase tracking-widest max-w-sm mx-auto">
            Internet connection lost. You can continue browsing cached views. 
            Sync will resume automatically.
        </p>

        <button onclick="window.location.reload()" class="px-8 py-4 bg-white text-black font-black uppercase text-xs rounded-full shadow-white/20 shadow-2xl hover:scale-105 active:scale-95 transition-all">
            Try Reconnect
        </button>
    </div>
</body>
</html>
