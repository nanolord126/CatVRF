@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-[120px] animate-pulse"></div>

        <div class="relative z-10">
            <h1 class="text-4xl font-black text-white italic tracking-tighter mb-4 opacity-90 uppercase">
                Конструкторы и калькуляторы <span class="text-indigo-400 font-bold ml-2">2026</span>
            </h1>
            <p class="text-slate-400 text-lg italic max-w-2xl mb-12">
                Профессиональные инструменты для расчета стоимости, объема материалов и визуализации мебели. 
                Решения на уровне Leroy Merlin и Hoff для вашего бизнеса.
            </p>

            <!-- Группы конструкторов -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                @foreach($templates as $template)
                    <a href="{{ route('account.configurator.show', $template->slug) }}" class="group block p-6 bg-white/5 border border-white/10 rounded-2xl hover:border-indigo-500 hover:bg-white/10 transition-all duration-300 transform hover:-translate-y-2 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-100 group-hover:text-indigo-500 transition-opacity">
                            <span class="text-4xl font-black italic">PRO</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2 italic">{{ $template->name }}</h3>
                        <p class="text-sm text-slate-400 mb-4 line-clamp-2 italic">{{ $template->meta['description'] ?? 'Конструирование и расчет параметров изделия' }}</p>
                        <div class="text-xs text-indigo-400 font-black uppercase tracking-widest">Перейти к созданию &rarr;</div>
                    </a>
                @endforeach
            </div>

            <!-- Пример калькулятора кирпича -->
            <div class="mb-16">
                <h2 class="text-2xl font-black text-white italic mb-8 uppercase tracking-widest border-l-4 border-indigo-500 pl-4">Строительные расчеты</h2>
                <x-configurators.brick-calculator />
            </div>

            <!-- FAQ Блок (Accordion) -->
            <div x-data="{ active: null }" class="max-w-4xl mx-auto space-y-4">
                <h3 class="text-2xl font-black text-white italic mb-6 uppercase text-center">Бизнес-инсайты (FAQ)</h3>
                
                <div class="border border-white/10 rounded-xl overflow-hidden bg-black/20">
                    <button @click="active = (active === 1 ? null : 1)" class="w-full flex justify-between items-center p-4 text-left hover:bg-white/5 transition-all">
                        <span class="text-white font-bold italic">Как продавать кастомную мебель и материалы через собственный сайт?</span>
                        <svg class="h-5 w-5 text-indigo-400 transform transition-transform" :class="active === 1 ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="p-4 text-slate-400 text-sm italic border-t border-white/5">
                        Кастомные решения требуют точных чертежей и спецификаций. Наш модуль генерирует PDF с картой раскроя и списком фурнитуры сразу после заказа, что минимизирует ошибки на производстве.
                    </div>
                </div>

                <div class="border border-white/10 rounded-xl overflow-hidden bg-black/20">
                    <button @click="active = (active === 2 ? null : 2)" class="w-full flex justify-between items-center p-4 text-left hover:bg-white/5 transition-all">
                        <span class="text-white font-bold italic">Как автоматически рассчитывать себестоимость и наценку?</span>
                        <svg class="h-5 w-5 text-indigo-400 transform transition-transform" :class="active === 2 ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="p-4 text-slate-400 text-sm italic border-t border-white/5">
                        В разделе опций конфигуратора задаются материальные затраты. При расчете на лету система применяет коэффициент наценки тенанта, указанный в настройках профиля.
                    </div>
                </div>

                <div class="border border-white/10 rounded-xl overflow-hidden bg-black/20">
                    <button @click="active = (active === 3 ? null : 3)" class="w-full flex justify-between items-center p-4 text-left hover:bg-white/5 transition-all">
                        <span class="text-white font-bold italic">Что делать с остатками материалов после точного расчёта?</span>
                        <svg class="h-5 w-5 text-indigo-400 transform transition-transform" :class="active === 3 ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="p-4 text-slate-400 text-sm italic border-t border-white/5">
                        Наш калькулятор учитывает процент обрезков (waste factor). Остатки можно автоматически передавать в модуль Sale/Outlet для реализации по сниженной цене или для мелких доп-услуг.
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/configurators/kitchen.js'])
@endpush
