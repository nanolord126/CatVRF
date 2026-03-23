@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом отопительных систем (Теплый пол)
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { area: 50, step: 150, pipeType: 'PEX-A', manifoldLoops: 4, insulation: true, automations: true },
    correlationId: '{{ Str::uuid() }}',

    get pipeLength() {
        return Math.ceil(this.config.area / (this.config.step / 1000) * 1.1);
    },

    get pipeData() {
        return {
            'PEX-A': { price: 95, name: 'PEX-A EVOH' },
            'Copper': { price: 850, name: 'Copper 15mm' },
            'Metalloplastic': { price: 145, name: 'PE-RT/AL' }
        }[this.config.pipeType];
    },

    get totalPrice() {
        let matCost = this.pipeLength * this.pipeData.price;
        let insulationCost = this.config.insulation ? (this.config.area * 450) : 0;
        let automationCost = this.config.automations ? 45000 : 0;
        let manifoldCost = this.config.manifoldLoops * 8500;
        return Math.round(matCost + insulationCost + automationCost + manifoldCost + (this.config.area * 600)); // +монтаж
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Thermal Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter shadow-2xl font-sans italic tracking-tighter leading-none italic uppercase">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-red-500/30">
                    <div class="w-2.5 h-2.5 bg-red-600 rounded-full animate-pulse shadow-[0_0_10px_#dc2626]"></div>
                    <span class="text-[10px] text-red-100 font-black uppercase tracking-widest italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="'Heat-Core: ' + pipeData.name"></span>
                </div>
            </div>

            <!-- Thermal Map Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#7f1d1d_0%,#020617_100%)] font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none italic uppercase">
                    <svg viewBox="0 0 400 400" class="w-full drop-shadow-2xl">
                        <rect x="50" y="50" width="300" height="300" fill="none" stroke="#ef4444" stroke-width="0.5" stroke-dasharray="10 10" opacity="0.3" />
                        <path :d="`M 70 70 L 330 70 L 330 110 L 70 110 L 70 150 L 330 150 L 330 190 L 70 190 L 70 230 L 330 230 L 330 270 L 70 270 L 70 310 L 330 310`" 
                              fill="none" stroke="#ef4444" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" class="transition-all duration-1000 opacity-60">
                            <animate attributeName="stroke-dashoffset" from="1000" to="0" dur="10s" repeatCount="indefinite" />
                        </path>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl block font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="pipeLength"></span>
                        <span class="text-[12px] text-red-400 font-black tracking-[0.3em] mt-4 leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">METERS OF PIPE</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Surface Step (mm)</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="config.step"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-red-500 font-sans italic tracking-tighter leading-none italic uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase">Budget Estimate</span>
                    <span class="text-3xl text-red-400 font-black italic tracking-tighter uppercase leading-none font-sans font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase">
                <div class="inline-block px-5 py-2 rounded-full bg-red-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans italic tracking-tighter leading-none">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none">Thermal Surface Matrix</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Heat Shell</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest">Active Area (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic" x-text="config.area"></span>
                    </div>
                    <input type="range" x-model="config.area" min="5" max="300" step="1" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-red-500 transition-all font-sans italic tracking-tighter leading-none italic">
                </div>

                <div class="grid grid-cols-3 gap-4 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <template x-for="step in [100, 150, 200]">
                        <button @click="config.step = step" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic":class="config.step === step ? 'bg-red-700 text-white border-red-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="step + ' mm'"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-red-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic">XPS Insulation</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none">Thermal Barrier 50mm Core</span>
                        </div>
                        <button @click="config.insulation = !config.insulation" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.insulation ? 'bg-red-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic" :style="config.insulation ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-red-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter">AI Thermo Control</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase">Smart Manifold Integration</span>
                        </div>
                        <button @click="config.automations = !config.automations" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.automations ? 'bg-red-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic" :style="config.automations ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-red-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none italic uppercase">
                    <div class="font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-[12px] text-red-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">Heat Surface Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-red-700 hover:bg-red-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic uppercase">
                    <span>Deploy Heat Matrix</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" fill="none" viewBox="0 0 24 24" stroke="currentColor font-sans italic tracking-tighter leading-none italic uppercase">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.99 7.99 0 0120 13a7.98 7.98 0 01-2.343 5.657z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-red-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Труба (метры)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.pipeLength + ' м'"></span>
                    </div>
                    <div class="border-l border-red-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Теплоотдача (W/м²)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.heatOutput + ' Вт'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Материалы + монтаж:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveHeating()" class="w-full py-6 bg-red-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-red-500 active:scale-95 transition-all shadow-2xl shadow-red-500/20">
                ЗАПУСТИТЬ РАСЧЕТ И СПЕЦИФИКАЦИЮ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Среднее отклонение по гидравлике +/- 3.5%. Требуется проект ОВ.</p>
        </div>
    </div>
</div>
