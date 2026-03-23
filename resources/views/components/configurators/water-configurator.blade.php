@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом водоподготовки
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { source: 'well', osmosis: true, uv: true, mineralization: 150, volume: 500, flow: true, purity: 98, hardness: 2 },
    correlationId: '{{ Str::uuid() }}',

    get stages() {
        return [
            { name: 'Sediment', level: 100 },
            { name: 'Carbon', level: 90 },
            { name: 'Osmosis', level: this.config.osmosis ? 95 : 0 },
            { name: 'Mineral', level: (this.config.mineralization / 300) * 100 }
        ];
    },

    get totalPrice() {
        let basePrice = this.config.source === 'city' ? 45000 : 85000;
        let osmosisCost = this.config.osmosis ? 25000 : 0;
        let uvCost = this.config.uv ? 18000 : 0;
        let volumeMultiplier = this.config.volume / 500;
        return Math.round((basePrice + osmosisCost + uvCost) * volumeMultiplier + 15000); // +монтаж
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase font-sans italic tracking-tighter">
        
        <!-- Water Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter shadow-2xl font-sans italic tracking-tighter leading-none italic uppercase">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-blue-500/30 font-sans italic tracking-tighter leading-none">
                    <div class="w-2.5 h-2.5 bg-blue-600 rounded-full animate-pulse shadow-[0_0_10px_#2563eb]"></div>
                    <span class="text-[10px] text-blue-100 font-black uppercase tracking-widest italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase px-2 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none" x-text="'Hydra-Core: ' + config.source"></span>
                </div>
            </div>

            <!-- Fluidic Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#1e3a8a_0%,#020617_100%)] font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none italic uppercase">
                    <svg viewBox="0 0 400 400" class="w-full opacity-60">
                        <!-- Main Pipe -->
                        <path d="M 50 200 L 350 200" stroke="#3b82f6" stroke-width="40" stroke-opacity="0.1" fill="none" rx="20" />
                        
                        <!-- Filter Nodes -->
                        <template x-for="(stage, i) in stages">
                            <g :transform="`translate(${80 + i*60}, 150)`">
                                <rect width="40" height="100" fill="#1e40af" fill-opacity="0.2" stroke="#60a5fa" stroke-width="1.5" rx="10" />
                                <rect width="40" :height="stage.level" :y="100 - stage.level" fill="#3b82f6" fill-opacity="0.4" rx="10" />
                            </g>
                        </template>

                        <!-- Flow Particles -->
                        <template x-if="config.flow">
                            <g>
                                <template x-for="n in 12">
                                    <circle r="3" fill="#60a5fa">
                                        <animateMotion :path="`M ${50 + n*20} 200 L 400 200`" :dur="`${1 + Math.random()}s`" repeatCount="indefinite" />
                                    </circle>
                                </template>
                            </g>
                        </template>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl block font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none" x-text="config.purity + '%'"></span>
                        <span class="text-[12px] text-blue-400 font-black tracking-[0.3em] mt-4 leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">PURITY MATRIX</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Volume Loop (L/D)</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="config.volume"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-blue-500 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase">Lifecycle Cost</span>
                    <span class="text-3xl text-blue-400 font-black italic tracking-tighter uppercase leading-none font-sans font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                <div class="inline-block px-5 py-2 rounded-full bg-blue-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none">Fluidic Flow Dynamic</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Hydra Pure</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest">Daily Consumption (L)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic" x-text="config.volume"></span>
                    </div>
                    <input type="range" x-model="config.volume" min="100" max="2500" step="50" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-blue-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                    <template x-for="s in ['city', 'well']">
                        <button @click="config.source = s" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic":class="config.source === s ? 'bg-blue-700 text-white border-blue-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="s"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-blue-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">RO Membrane Matrix</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">Molecular Precision Shield</span>
                        </div>
                        <button @click="config.osmosis = !config.osmosis" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :class="config.osmosis ? 'bg-blue-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :style="config.osmosis ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-blue-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">UV-C Bio Guardian</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase">Pathogen Eradication Core</span>
                        </div>
                        <button @click="config.uv = !config.uv" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :class="config.uv ? 'bg-blue-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :style="config.uv ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                <div class="absolute inset-x-0 h-[1px] bg-blue-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter font-sans italic tracking-tighter"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter">
                    <div class="font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                        <span class="text-[12px] text-blue-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">Fluid-Net Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-blue-700 hover:bg-blue-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase">
                    <span>Activate Hydro Sync</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter leading-none italic uppercase" fill="none" viewBox="0 0 24 24" stroke="currentColor font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
