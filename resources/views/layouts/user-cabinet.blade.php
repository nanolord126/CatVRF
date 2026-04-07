<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Личный кабинет' }} — {{ config('app.name', 'CatVRF') }}</title>
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@1,2&f[]=inter@1,2&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-gray-50 font-sans text-gray-900">

<div class="flex min-h-screen" x-data="{ sidebarOpen: window.innerWidth >= 1024 }">

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-100 shadow-sm flex flex-col transition-transform duration-300"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        {{-- Logo --}}
        <div class="h-16 flex items-center gap-3 px-5 border-b border-gray-100">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm">К</div>
            <span class="font-bold text-gray-900">КОТВ.РФ</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="{{ route('user.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.dashboard') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">🏠</span> Главная
            </a>
            <a href="{{ route('user.ai-constructor') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.ai-constructor') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">🤖</span> AI-конструктор
            </a>
            <a href="{{ route('user.wallet') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.wallet') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">💳</span> Кошелёк
            </a>
            <a href="{{ route('user.orders') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.orders') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">📦</span> Заказы
            </a>
            <a href="{{ route('user.addresses') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.addresses') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">📍</span> Адреса
            </a>
            <a href="{{ route('user.delivery-track') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                   {{ request()->routeIs('user.delivery-track') ? 'bg-purple-50 text-purple-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-lg">🚴</span> Трекинг
            </a>

            <div class="border-t border-gray-100 my-2"></div>

            <a href="/"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                <span class="text-lg">🛍</span> Маркетплейс
            </a>
        </nav>

        {{-- User footer --}}
        @auth
            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2 text-xs text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                        Выйти
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen && window.innerWidth < 1024"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/30 lg:hidden"
         x-transition.opacity></div>

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">

        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/" class="hover:text-gray-700 transition">Главная</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ $title ?? 'Кабинет' }}</span>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-gray-600 hidden sm:block">{{ auth()->user()->name }}</span>
                @endauth
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

    </div>
</div>

@livewireScripts
@stack('scripts')

<script>
document.addEventListener('livewire:navigated', () => {
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el._x_dataStack) Alpine.destroyTree(el);
    });
});
</script>
</body>
</html>
