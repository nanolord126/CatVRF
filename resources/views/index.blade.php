@extends('layouts.app')

@section('content')
<!-- Hero Section 2026 -->
<div class="relative py-20 overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center relative z-10">
        <div class="space-y-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-mint-500/10 border border-mint-500/20 text-mint-400 text-xs font-bold uppercase tracking-wider">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-mint-500 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-mint-500"></span>
                </span>
                Статус экосистемы: Активна
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold font-sans leading-[1.1] tracking-tight text-white">
                КОТВ.РФ <br>
                <span class="text-xs md:text-sm text-mint-400 block mt-2 font-medium uppercase tracking-[0.3em]">
                    меняем образ маркетплейсов
                </span>
            </h1>

            <p class="text-lg text-carbon-400 leading-relaxed max-w-lg">
                Масштабируемая мульти-тенант платформа 2026. Отели, клиники и B2B поставки в едином контуре с AI-аналитикой и высокой безопасностью
            </p>

            <div class="flex flex-wrap gap-4 pt-4">
                <a href="/admin" class="px-8 py-4 bg-mint-600 text-white font-bold rounded-2xl shadow-lg shadow-mint-500/20 hover:bg-mint-500 transition-all transform hover:-translate-y-1">
                    Войти в систему
                </a>
            </div>
        </div>

        <!-- Dashboard Preview -->
        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-neuro-indigo-500 to-organic-teal-500 rounded-[32px] blur opacity-25 group-hover:opacity-40 transition-opacity"></div>
            <div class="relative bg-black/60 backdrop-blur-3xl border border-white/10 rounded-[40px] p-12 shadow-2xl overflow-hidden aspect-video flex flex-col justify-center items-center">
                <div class="text-center space-y-4">
                    <div class="text-7xl font-black text-white tracking-tighter drop-shadow-2xl">1.2M+</div>
                    <div class="text-base text-neuro-indigo-400 uppercase tracking-[0.3em] font-black drop-shadow-lg">
                        Событий обработано AI
                    </div>
                </div>
                <!-- Abstract UI lines -->
                <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-gradient-to-r from-transparent via-neuro-indigo-500 to-transparent opacity-60"></div>
            </div>
        </div>
    </div>
    
    <!-- Background Decor -->
    <div class="absolute top-0 right-0 -translate-y-12 translate-x-12 w-96 h-96 bg-neuro-indigo-600/10 blur-[120px] rounded-full"></div>
    <div class="absolute bottom-0 left-0 translate-y-24 -translate-x-24 w-[500px] h-[500px] bg-organic-teal-600/10 blur-[150px] rounded-full"></div>
</div>

<!-- Vertical Grid -->
<div id="verticals" class="max-w-7xl mx-auto px-6 py-24 border-t border-white/5">
    <div class="mb-16">
        <h2 class="text-4xl font-bold text-white mb-4">Вертикали 2026</h2>
        <p class="text-carbon-400">Изолированные модули для высокой нагрузки и AI-оптимизации.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-8 items-stretch">
        <div class="group relative p-8 rounded-3xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all flex flex-col h-full">
            <div class="w-12 h-12 rounded-xl bg-neuro-indigo-500/20 flex items-center justify-center text-neuro-indigo-400 mb-6 group-hover:scale-110 transition-transform flex-shrink-0">🏨</div>
            <h3 class="text-xl font-bold text-white mb-3">Отели (ВИП)</h3>
            <p class="text-sm text-carbon-400 leading-relaxed mb-6 flex-grow">Управление отелями, апартаментами и бронированием с поддержкой фискализации платежей.</p>
            <div class="mt-auto">
                <span class="text-[10px] font-black text-neuro-indigo-400 uppercase tracking-[0.2em] border border-neuro-indigo-400/20 px-3 py-1.5 rounded-lg bg-neuro-indigo-500/5">Доступно</span>
            </div>
        </div>
        
        <div class="group relative p-8 rounded-3xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all flex flex-col h-full">
            <div class="w-12 h-12 rounded-xl bg-organic-teal-500/20 flex items-center justify-center text-organic-teal-400 mb-6 group-hover:scale-110 transition-transform flex-shrink-0">🏥</div>
            <h3 class="text-xl font-bold text-white mb-3">Медицина</h3>
            <p class="text-sm text-carbon-400 leading-relaxed mb-6 flex-grow">Автоматизация клиник, электронные медкарты и телемедицина нового поколения.</p>
            <div class="mt-auto">
                <span class="text-[10px] font-black text-organic-teal-400 uppercase tracking-[0.2em] border border-organic-teal-400/20 px-3 py-1.5 rounded-lg bg-organic-teal-500/5">Масштабирование</span>
            </div>
        </div>

        <div class="group relative p-8 rounded-3xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all flex flex-col h-full">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition-transform flex-shrink-0">🚕</div>
            <h3 class="text-xl font-bold text-white mb-3">Логистика (ВИП)</h3>
            <p class="text-sm text-carbon-400 leading-relaxed mb-6 flex-grow">Системы доставки и вызова такси с алгоритмами динамического ценообразования.</p>
            <div class="mt-auto">
                <span class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em] border border-amber-500/20 px-3 py-1.5 rounded-lg bg-amber-500/5">Ранний доступ</span>
            </div>
        </div>
    </div>
</div>
@endsection
