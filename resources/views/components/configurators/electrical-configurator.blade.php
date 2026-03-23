@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом электрических систем
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { outletCount: 40, circuitCount: 12, protectionClass: 'ABB Pro', stabilizer: true, ups: true },
    correlationId: '{{ Str::uuid() }}',

    get cableLength() {
        return Math.ceil(this.config.outletCount * 12 + this.config.circuitCount * 15);
    },

    get maxPowerKw() {
        return (this.config.circuitCount * 2.5).toFixed(1);
    },

    get totalPrice() {
        let cableCost = this.cableLength * 85;
        let protectionCost = this.config.circuitCount * (this.config.protectionClass === 'ABB Pro' ? 4500 : 2800);
        let stabilizerCost = this.config.stabilizer ? 85000 : 0;
        let upsCost = this.config.ups ? 45000 : 0;
        let laborCost = this.config.outletCount * 1200 + this.config.circuitCount * 3500;
        return Math.round(cableCost + protectionCost + stabilizerCost + upsCost + laborCost + 25000); // +щит
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase font-sans italic tracking-tighter">
        
        <!-- Power Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter shadow-2xl font-sans italic tracking-tighter leading-none italic uppercase">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-cyan-500/30 font-sans italic tracking-tighter leading-none">
                    <div class="w-2.5 h-2.5 bg-cyan-600 rounded-full animate-pulse shadow-[0_0_10px_#0891b2]"></div>
                    <span class="text-[10px] text-cyan-100 font-black uppercase tracking-widest italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase px-2 font-sans italic tracking-tighter leading-none italic uppercase" x-text="'Grid-Core: ' + config.protectionClass"></span>
                </div>
            </div>

            <!-- Circuit Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#083344_0%,#020617_100%)] font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none italic uppercase">
                    <svg viewBox="0 0 400 400" class="w-full opacity-60">
                        <template x-for="i in config.circuitCount">
                            <path :d="`M 50 ${50 + (i*300/config.circuitCount)} L 350 ${50 + (i*300/config.circuitCount)}`" 
                                  fill="none" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="10 20" opacity="0.4">
                                <animate attributeName="stroke-dashoffset" from="1000" to="0" dur="5s" repeatCount="indefinite" />
                            </path>
                        </template>
                        <rect x="50" y="50" width="40" height="300" fill="#164e63" stroke="#06b6d4" stroke-width="2" rx="4" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl block font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="maxPowerKw"></span>
                        <span class="text-[12px] text-cyan-400 font-black tracking-[0.3em] mt-4 leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">MAX LOAD (kW)</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Cable Matrix (m)</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="cableLength"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-cyan-500 font-sans italic tracking-tighter leading-none italic uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase">Grid Integration</span>
                    <span class="text-3xl text-cyan-400 font-black italic tracking-tighter uppercase leading-none font-sans font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter">
                <div class="inline-block px-5 py-2 rounded-full bg-cyan-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans italic tracking-tighter leading-none">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none">Power Distribution Flux</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Energy Cell</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest">Power Points (Outlets)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none" x-text="config.outletCount"></span>
                    </div>
                    <input type="range" x-model="config.outletCount" min="10" max="300" step="10" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-cyan-500 transition-all font-sans italic tracking-tighter leading-none italic">
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <template x-for="p in ['ABB Pro', 'Legrand TX']">
                        <button @click="config.protectionClass = p" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic":class="config.protectionClass === p ? 'bg-cyan-700 text-white border-cyan-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="p"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-cyan-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">Voltage Flux Stabilizer</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">Lider Precision Grade Core</span>
                        </div>
                        <button @click="config.stabilizer = !config.stabilizer" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.stabilizer ? 'bg-cyan-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic" :style="config.stabilizer ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-cyan-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">UPS Backup Cluster</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">APC Smart-UPS IT Matrix</span>
                        </div>
                        <button @click="config.ups = !config.ups" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.ups ? 'bg-cyan-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic" :style="config.ups ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                <div class="absolute inset-x-0 h-[1px] bg-cyan-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                    <div class="font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                        <span class="text-[12px] text-cyan-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">Power Grid Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-cyan-700 hover:bg-cyan-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic">
                    <span>Initiate Grid Sync</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase" fill="none" viewBox="0 0 24 24" stroke="currentColor font-sans italic tracking-tighter leading-none italic uppercase">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                    </div>
                    <div class="border-l border-cyan-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Max Нагрузка (А)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.maxAmps + ' А'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Электрощита:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveElectrical()" class="w-full py-6 bg-cyan-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-cyan-500 active:scale-95 transition-all shadow-2xl shadow-cyan-500/20">
                СГЕНЕРИРОВАТЬ СХЕМУ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Проектирование согласно ПУЭ-7 и ГОСТ Р 50571.</p>
        </div>
    </div>
</div>
