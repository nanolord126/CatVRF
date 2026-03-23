@extends('layouts.app')

@section('title', $template->name . ' - Configurator 2.0')

@section('content')
<div class="min-h-screen bg-[#0a0a0c] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-12">
        <!-- Навигация -->
        <div class="flex items-center justify-between border-b border-white/5 pb-8">
            <div class="flex items-center space-x-6">
                <a href="{{ route('account.configurator.dashboard') }}" class="p-3 bg-white/5 rounded-2xl hover:bg-white/10 transition-all border border-white/5 group">
                    <svg class="w-6 h-6 text-slate-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-white italic tracking-tighter uppercase">{{ $template->name }}</h1>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest italic mt-1">{{ $template->category }} / Industrial Standard 2026</p>
                </div>
            </div>
            
            <div class="hidden md:flex space-x-3">
                <span class="px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-[10px] text-emerald-400 font-bold uppercase italic">Cloud Sync Active</span>
                <span class="px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full text-[10px] text-indigo-400 font-bold uppercase italic tracking-tighter">Correlation ID: {{ Str::random(8) }}</span>
            </div>
        </div>

        <!-- Контент конструктора -->
        <div class="relative">
            @if($template->type === 'kitchen')
                <x-configurators.kitchen-configurator :template="$template" />
            @elseif($template->type === 'wardrobe')
                <x-configurators.wardrobe-configurator :template="$template" />
            @elseif($template->type === 'staircase')
                <x-configurators.staircase-configurator :template="$template" />
            @elseif($template->type === 'flooring')
                <x-configurators.flooring-calculator :template="$template" />
            @elseif($template->type === 'roof')
                <x-configurators.roof-calculator :template="$template" />
            @elseif($template->type === 'door')
                <x-configurators.door-configurator :template="$template" />
            @elseif($template->type === 'window')
                <x-configurators.window-configurator :template="$template" />
            @elseif($template->type === 'foundation')
                <x-configurators.foundation-calculator :template="$template" />
            @elseif($template->type === 'heating')
                <x-configurators.heating-calculator :template="$template" />
            @elseif($template->type === 'brick_mortar')
                <x-configurators.brick-calculator :template="$template" />
            @else
                <div class="p-20 text-center bg-white/5 border border-dashed border-white/10 rounded-3xl">
                    <p class="text-slate-500 italic font-bold">Конструктор для типа "{{ $template->type }}" находится в разработке...</p>
                </div>
            @endif
        </div>

        <!-- Техническая подножка -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-12 border-t border-white/5">
            <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                <h4 class="text-white text-sm font-black italic uppercase mb-2">Автоматизация</h4>
                <p class="text-slate-500 text-xs leading-relaxed italic">Все расчеты соответствуют СНиП 2.03.11-85 и ГОСТ Р 52059-2003. Чертежи генерируются в форматах .DWG и .PDF.</p>
            </div>
            <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                <h4 class="text-white text-sm font-black italic uppercase mb-2">Логистика</h4>
                <p class="text-slate-500 text-xs leading-relaxed italic">Срок производства: 14-21 день. Доставка по РФ через ТК ПЭК/Деловые Линии. Упаковка в защитный короб включена.</p>
            </div>
            <div class="p-6 bg-white/10 rounded-3xl border border-indigo-500/20">
                <h4 class="text-white text-sm font-black italic uppercase mb-2 text-indigo-400">Гарантия</h4>
                <p class="text-slate-500 text-xs leading-relaxed italic">Расширенная гарантия 5 лет на фурнитуру Blum/Hettich и 10 лет на металлокаркасы лестниц.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/configurators/kitchen.js'])
@endpush
