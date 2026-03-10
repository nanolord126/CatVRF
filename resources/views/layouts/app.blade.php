<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CatVRF Ecosystem') }}</title>
    
    <!-- Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@1,2&f[]=inter@1,2&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-[var(--theme-bg,#050505)] text-carbon-50 font-sans selection:bg-neuro-indigo-500 selection:text-white transition-colors duration-500">
    
    <!-- Glassmorphic Header -->
    <header class="fixed top-0 w-full z-50 bg-black/40 backdrop-blur-xl border-b border-white/5">
        <nav class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold bg-gradient-to-r from-neuro-indigo-400 to-organic-teal-400 bg-clip-text text-transparent">
                КОТВ.РФ
            </div>
            <div class="hidden md:flex gap-8 text-sm font-medium text-carbon-300">
                <a href="#" class="hover:text-white transition-colors">Маркетплейс</a>
                <a href="#verticals" class="hover:text-white transition-colors">Вертикали</a>
                <a href="#" class="hover:text-white transition-colors">B2B Поставки</a>
                <a href="#" class="hover:text-white transition-colors">AI Аналитика</a>
            </div>
            <div class="flex items-center gap-6" x-data="{ open: false }">
                <!-- Theme Picker in Header -->
                <div class="relative group" @click.away="open = false" @scroll.window="open = false">
                    <button @click="open = !open" class="px-4 py-2 bg-white/5 backdrop-blur-xl border border-white/10 text-white text-xs font-bold rounded-xl hover:bg-white/10 transition-colors inline-flex items-center gap-2">
                        <span>Тема</span>
                        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute top-full right-0 mt-2 w-48 bg-black/90 backdrop-blur-3xl border border-white/10 rounded-2xl shadow-2xl p-2 z-50">
                        <button onclick="ThemeManager.setTheme('midnight');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Полночь</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                        <button onclick="ThemeManager.setTheme('mint');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Мята</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                        <button onclick="ThemeManager.setTheme('dawn');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Рассвет</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                        <button onclick="ThemeManager.setTheme('cobalt');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Кобальт</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                        <button onclick="ThemeManager.setTheme('graphite');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Графит</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                        <button onclick="ThemeManager.setTheme('sapphire');" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-white/10 rounded-lg text-xs text-white flex items-center justify-between group/item">
                            <span>Сапфир</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 opacity-0 group-hover/item:opacity-100"></span>
                        </button>
                    </div>
                </div>

                <a href="{{ url('/admin') }}" class="px-6 py-2 bg-neuro-indigo-600 hover:bg-neuro-indigo-500 rounded-full text-sm font-semibold transition-all shadow-lg shadow-neuro-indigo-500/20">
                    Войти
                </a>
            </div>
        </nav>
    </header>

    <main class="pt-24 min-h-screen">
        @yield('content')
        
        <!-- AI Suggestion Section -->
        <section class="max-w-7xl mx-auto px-6 py-20">
            <div class="p-8 rounded-[32px] bg-gradient-to-br from-neuro-indigo-900/20 to-organic-teal-900/10 border border-white/5 backdrop-blur-sm">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-mint-500/20 flex items-center justify-center text-xs">✨</span>
                    Персональные рекомендации AI
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @forelse($ai_suggestions ?? [] as $suggestion)
                        <div class="p-6 rounded-2xl bg-white/5 hover:bg-white/10 transition-colors border border-white/5 text-sm text-carbon-300">
                            {{ $suggestion }}
                        </div>
                    @empty
                        <div class="col-span-3 text-carbon-500 italic text-sm">Анализируем тренды экосистемы...</div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <!-- Glassmorphic Footer -->
    <footer class="bg-black py-16 border-t border-white/5 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-2 lg:grid-cols-4 gap-12 text-sm">
            <div>
                <h4 class="text-white font-black uppercase tracking-widest text-xs mb-6 px-3 py-1 border-l-2 border-mint-500 inline-block">Платформы</h4>
                <ul class="space-y-4 text-carbon-400">
                    <li><a href="#" class="hover:text-mint-400 transition-colors">Цветы & Декор</a></li>
                    <li><a href="#" class="hover:text-mint-400 transition-colors">Рестораны & Еда</a></li>
                    <li><a href="#" class="hover:text-mint-400 transition-colors">Такси & Логистика</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-black uppercase tracking-widest text-xs mb-6 px-3 py-1 border-l-2 border-neuro-indigo-500 inline-block">Экосистема</h4>
                <ul class="space-y-4 text-carbon-400">
                    <li><a href="#" class="hover:text-neuro-indigo-400 transition-colors">О проекте</a></li>
                    <li><a href="#" class="hover:text-neuro-indigo-400 transition-colors">Карьера</a></li>
                    <li><a href="#" class="hover:text-neuro-indigo-400 transition-colors">ESG устойчивость</a></li>
                </ul>
            </div>
            <div class="col-span-2 text-right">
                <h4 class="text-white font-bold mb-6">Будущее автоматизировано.</h4>
                <p class="text-carbon-500 leading-relaxed ml-auto max-w-sm">
                    КОТВ.РФ — Мульти-тенантная инфраструктура, работающая на алгоритмах AI/ML для экономики 2026 года.
                </p>
                <div class="mt-8 flex justify-end gap-6 grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all">
                    <span class="text-[10px] font-bold text-carbon-600 tracking-widest uppercase">РА ДВИЖ ПАРИЖ</span>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
