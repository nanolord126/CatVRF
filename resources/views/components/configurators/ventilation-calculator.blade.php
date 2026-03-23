@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом климатических систем
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { sqm: 100, persons: 4, systemType: 'balanced', recuperatorEnabled: true, hepaFilter: true },
    correlationId: '{{ Str::uuid() }}',

    get airflow() {
        let baseFlow = Math.max(this.config.sqm * 3, this.config.persons * 60);
        return Math.ceil(baseFlow);
    },

    get powerConsumption() {
        let base = this.airflow / 1000;
        if (this.config.recuperatorEnabled) base *= 0.3;
        if (this.config.hepaFilter) base *= 1.2;
        return base.toFixed(1);
    },

    get totalPrice() {
        let basePrice = this.airflow * 450;
        if (this.config.recuperatorEnabled) basePrice += 120000;
        if (this.config.hepaFilter) basePrice += 45000;
        if (this.config.systemType === 'balanced') basePrice *= 1.5;
        return Math.round(basePrice);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Airflow Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-blue-500/30">
                    <div class="w-2.5 h-2.5 bg-blue-600 rounded-full animate-pulse shadow-[0_0_10px_#2563eb]"></div>
                    <span class="text-[10px] text-blue-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Flux-Core: ' + config.systemType"></span>
                </div>
            </div>

            <!-- Flow Dynamics Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#1e3a8a_0%,#020617_100%)] font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none italic uppercase">
                    <svg viewBox="0 0 400 400" class="w-full drop-shadow-2xl opacity-40">
                        <circle cx="200" cy="200" r="150" fill="none" stroke="#3b82f6" stroke-width="1" stroke-dasharray="10 20" />
                        <path d="M 100 200 Q 200 100 300 200 Q 200 300 100 200" fill="none" stroke="#3b82f6" stroke-width="4" stroke-linecap="round">
                            <animate attributeName="stroke-dashoffset" from="1000" to="0" dur="5s" repeatCount="indefinite" />
                        </path>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl block font-sans italic tracking-tighter leading-none italic uppercase" x-text="airflow"></span>
                        <span class="text-[12px] text-blue-400 font-black tracking-[0.3em] mt-4 leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">M³/H FLOW</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Energy Flux (kW)</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="powerConsumption"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-blue-500">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Budget Allocation</span>
                    <span class="text-3xl text-blue-400 font-black italic tracking-tighter uppercase leading-none font-sans" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase">
                <div class="inline-block px-5 py-2 rounded-full bg-blue-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none uppercase">Climate Breather Matrix</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Atmos Core</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter">Floor Area (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic" x-text="config.sqm"></span>
                    </div>
                    <input type="range" x-model="config.sqm" min="10" max="500" step="5" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-blue-500 transition-all font-sans italic tracking-tighter leading-none">
                </div>

                <div class="grid grid-cols-3 gap-4 font-sans italic tracking-tighter leading-none italic">
                    <template x-for="type in ['supply', 'exhaust', 'balanced']">
                        <button @click="config.systemType = type" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[9px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none":class="config.systemType === type ? 'bg-blue-700 text-white border-blue-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="type"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-blue-500 transition-all font-sans italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter italic">Recuperator Unit</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic">Heat Recovery 85% Efficiency</span>
                        </div>
                        <button @click="config.recuperatorEnabled = !config.recuperatorEnabled" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.recuperatorEnabled ? 'bg-blue-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md" :style="config.recuperatorEnabled ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-blue-500 transition-all font-sans italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter italic">HEPA Medical Core</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic tracking-tighter leading-none italic">Grade H13 Filtration 99.9%</span>
                        </div>
                        <button @click="config.hepaFilter = !config.hepaFilter" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.hepaFilter ? 'bg-blue-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md" :style="config.hepaFilter ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-blue-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none">
                    <div class="font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-[12px] text-blue-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic uppercase">Atmos Integration Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-blue-700 hover:bg-blue-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic">
                    <span>Initiate Flux Control</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" fill="none" viewBox="0 0 24 24" stroke="currentColor font-sans italic tracking-tighter leading-none italic">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>

            <button @click="saveVentilation()" class="w-full py-6 bg-blue-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-blue-500 active:scale-95 transition-all shadow-2xl shadow-blue-500/20">
                СФОРМИРОВАТЬ ИНЖЕНЕРНЫЙ ПАСПОРТ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет параметров по ASHRAE 62.1 и СП 60.13330.2020.</p>
        </div>
    </div>
</div>
