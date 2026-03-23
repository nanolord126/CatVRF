@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом солнечной энергетики
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { panelCount: 20, batteryKw: 10, panelType: 'Mono', hybridInverter: true, netMetering: false },
    correlationId: '{{ Str::uuid() }}',

    get generation() {
        let eff = this.config.panelType === 'Mono' ? 0.450 : 0.380;
        return (this.config.panelCount * eff).toFixed(1);
    },

    get totalPrice() {
        let panelCost = this.config.panelCount * (this.config.panelType === 'Mono' ? 18000 : 12000);
        let batteryCost = this.config.batteryKw * 45000;
        let inverter = this.config.hybridInverter ? 125000 : 45000;
        let installation = 85000;
        return Math.round(panelCost + batteryCost + inverter + installation);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Solar Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl italic tracking-tighter leading-none uppercase">
            <div class="absolute top-10 left-10 z-40 italic tracking-tighter leading-none uppercase">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-yellow-500/30">
                    <div class="w-2.5 h-2.5 bg-yellow-600 rounded-full animate-pulse shadow-[0_0_10px_#eab308]"></div>
                    <span class="text-[10px] text-yellow-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Solar-Core: ' + config.panelType"></span>
                </div>
            </div>

            <!-- Panel Grid Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,#451a03_0%,#020617_100%)] italic tracking-tighter leading-none uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 italic tracking-tighter leading-none uppercase">
                    <svg viewBox="0 0 400 400" class="w-full opacity-60">
                        <!-- Roof Surface -->
                        <path d="M 50 150 L 200 50 L 350 150 L 350 350 L 50 350 Z" fill="#111827" stroke="#1f2937" stroke-width="2" />
                        
                        <!-- Panel Grid -->
                        <g transform="translate(80, 180) skewX(-10)">
                            <template x-for="r in 4">
                                <g :transform="'translate(0, ' + (r-1)*40 + ')'">
                                    <template x-for="c in 5">
                                        <rect :x="(c-1)*50" y="0" width="40" height="30" 
                                              :fill="config.panelType === 'Mono' ? '#1e3a8a' : '#2563eb'" 
                                              stroke="#3b82f6" stroke-width="1" rx="2">
                                            <animate attributeName="fill-opacity" values="0.4;0.8;0.4" dur="3s" :begin="(r+c)*0.2" repeatCount="indefinite" />
                                        </rect>
                                    </template>
                                </g>
                            </template>
                        </g>

                        <!-- Energy Rays -->
                        <g class="animate-pulse">
                            <line x1="200" y1="0" x2="200" y2="40" stroke="#f59e0b" stroke-width="2" />
                            <line x1="150" y1="10" x2="160" y2="45" stroke="#f59e0b" stroke-width="2" />
                            <line x1="250" y1="10" x2="240" y2="45" stroke="#f59e0b" stroke-width="2" />
                        </g>
                    </svg>
                    <div class="absolute inset-x-0 bottom-20 flex flex-col items-center justify-center italic tracking-tighter leading-none uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter block shadow-xl italic tracking-tighter leading-none uppercase font-sans" x-text="generation + ' KWp'"></span>
                        <span class="text-[12px] text-yellow-400 font-black tracking-[0.3em] mt-4 italic tracking-tighter font-sans leading-none">PEAK GENERATION</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl italic tracking-tighter leading-none uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans underline-offset-4 font-sans tracking-tighter">Battery Reserve</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase font-sans leading-none italic tracking-tighter leading-none uppercase" x-text="config.batteryKw + ' KWH'"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-yellow-500 italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase font-sans tracking-tighter">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase font-sans underline-offset-4 tracking-tighter">System Budget</span>
                    <span class="text-3xl text-yellow-500 font-black italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans tracking-tighter">
            <div class="mb-14 italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                <div class="inline-block px-5 py-2 rounded-full bg-yellow-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans tracking-tighter italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans tracking-tighter">Energy Harvest Vector</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">Solar Prime</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter italic tracking-tighter leading-none uppercase">PV Panel Cluster (Units)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none uppercase" x-text="config.panelCount"></span>
                    </div>
                    <input type="range" x-model="config.panelCount" min="8" max="120" step="4" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-yellow-500 transition-all italic tracking-tighter leading-none uppercase font-sans tracking-tighter">
                </div>

                <div class="grid grid-cols-2 gap-4 italic tracking-tighter leading-none uppercase">
                    <template x-for="t in ['Mono', 'Poly']">
                        <button @click="config.panelType = t" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl italic tracking-tighter leading-none uppercase":class="config.panelType === t ? 'bg-yellow-700 text-white border-yellow-500 shadow-[0_0_15px_#eab30844]' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="t + ' PERC'"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 italic tracking-tighter leading-none uppercase">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-yellow-500 transition-all italic tracking-tighter leading-none uppercase">
                        <div class="text-left italic tracking-tighter leading-none uppercase">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter leading-none uppercase font-sans tracking-tighter">Hybrid Inverter Logic</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic tracking-tighter leading-none uppercase">Smart-Grid Synchronizer</span>
                        </div>
                        <button @click="config.hybridInverter = !config.hybridInverter" class="w-14 h-7 rounded-full relative transition-all shadow-inner italic tracking-tighter leading-none uppercase" :class="config.hybridInverter ? 'bg-yellow-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md italic tracking-tighter leading-none uppercase" :style="config.hybridInverter ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-yellow-500 transition-all italic tracking-tighter leading-none uppercase">
                        <div class="text-left italic tracking-tighter leading-none uppercase">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase font-sans">ESS Battery Reserve</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic tracking-tighter leading-none uppercase font-sans tracking-tighter underline-offset-4">Lithium-Ion Storage Matrix</span>
                        </div>
                        <div class="flex items-center space-x-3 italic tracking-tighter leading-none uppercase">
                            <span class="text-xl font-black text-white italic leading-none font-sans pr-2 underline-offset-4 tracking-tighter" x-text="config.batteryKw + ' KWH'"></span>
                            <input type="range" x-model="config.batteryKw" min="5" max="60" step="5" class="w-24 h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-yellow-500 italic tracking-tighter leading-none uppercase font-sans tracking-tighter">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all italic tracking-tighter leading-none uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-yellow-500 top-0 opacity-40 italic tracking-tighter leading-none uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase font-sans tracking-tighter">
                    <div class="italic tracking-tighter leading-none uppercase underline-offset-4 font-sans tracking-tighter">
                        <span class="text-[12px] text-yellow-500 uppercase font-black block tracking-[0.2em] mb-4 italic italic tracking-tighter leading-none uppercase font-sans tracking-tighter">Solar-Core Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl italic tracking-tighter leading-none uppercase font-sans underline-offset-4 tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-yellow-700 hover:bg-yellow-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group italic tracking-tighter leading-none uppercase underline-offset-4 font-sans tracking-tighter">
                    <span>Initiate Solar Deployment</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform italic tracking-tighter leading-none uppercase underline-offset-4 font-sans tracking-tighter">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
