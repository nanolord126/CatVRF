@php
    declare(strict_types=1);
@endphp

<div x-data="{
    towers: 12,
    hydroponicLevels: 8,
    fullAutomation: true,
    spectralOptimization: true,
    co2Enrichment: true,
    
    get annualYield() {
        let base = this.towers * this.hydroponicLevels * 450;
        if (this.spectralOptimization) base *= 1.4;
        if (this.co2Enrichment) base *= 1.25;
        return (base / 1000).toFixed(1);
    },
    
    get waterEfficiency() {
        return this.fullAutomation ? '98%' : '85%';
    },

    get estimatedCost() {
        let base = this.towers * this.hydroponicLevels * 85000;
        if (this.fullAutomation) base += 25000000;
        if (this.spectralOptimization) base += 12000000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px]">
        
        <!-- Visual: Phyto-Tower Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl">
            <!-- HUD -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/80 backdrop-blur-2xl px-5 py-2.5 rounded-full border border-emerald-500/40 shadow-[0_0_40px_rgba(16,185,129,0.2)]">
                    <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-[ping_2s_linear_infinite]"></div>
                    <span class="text-[10px] text-emerald-400 font-black uppercase tracking-[0.3em] italic tracking-tighter leading-none">Phyto-Core v.4.2 Biosphere Active</span>
                </div>
            </div>

            <!-- Visualization: Aero-Farm Vertical Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(16,185,129,0.15)_0%,transparent_80%)]">
                <!-- Nutrient Flow Particles -->
                <div class="absolute inset-0 opacity-20 pointer-events-none">
                    <template x-for="i in 30">
                        <div class="absolute w-1 h-3 bg-emerald-400/40 rounded-full animate-[pulse_2s_infinite]" 
                             :style="`top: ${Math.random()*100}%; left: ${Math.random()*100}%; animation-delay: ${Math.random()*4}s` shadow-sm"></div>
                    </template>
                </div>

                <svg viewBox="0 0 400 400" class="w-full h-full drop-shadow-[0_0_120px_rgba(16,185,129,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Vertical Tower Enclosure -->
                    <rect x="120" y="50" width="160" height="300" fill="rgba(16,185,129,0.02)" stroke="#10b981" stroke-width="2" rx="20" stroke-dasharray="8 4" class="opacity-40" />
                    
                    <!-- Foliage Levels -->
                    <template x-for="i in parseInt(hydroponicLevels)">
                        <g :transform="`translate(0, ${60 + (i-1)*35})` shadow-sm">
                            <rect x="140" y="0" width="120" height="15" rx="4" 
                                  :fill="spectralOptimization ? 'rgba(16,185,129,0.4)' : 'rgba(255,255,255,0.05)'" 
                                  class="transition-all duration-1000 shadow-sm" />
                            <circle x="150" y="-5" r="3" fill="#10b981" class="animate-pulse shadow-sm" />
                        </g>
                    </template>

                    <!-- Spectral Bloom Glow -->
                    <rect x="120" y="50" width="160" height="300" 
                          :fill="spectralOptimization ? 'url(#spectralGradient)' : 'transparent'" 
                          class="transition-opacity duration-1000 opacity-20 pointer-events-none shadow-sm" />
                    
                    <defs>
                        <linearGradient id="spectralGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#ec4899" />
                            <stop offset="50%" stop-color="#10b981" />
                            <stop offset="100%" stop-color="#6366f1" />
                        </linearGradient>
                    </defs>
                </svg>

                <!-- Environment Telemetry -->
                <div class="absolute bottom-16 inset-x-12 grid grid-cols-2 gap-8 leading-none px-4 shadow-sm italic shadow-sm leading-none italic shadow-sm">
                    <div class="space-y-3 shadow-sm italic leading-none shadow-sm leading-none italic shadow-sm">
                        <div class="text-[8px] text-emerald-400 font-black uppercase tracking-widest leading-none italic shadow-sm italic shadow-sm">O2 Output</div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden shadow-sm italic shadow-sm shadow-sm italic shadow-sm italic">
                            <div class="h-full bg-emerald-500 animate-[pulse_2s_infinite] shadow-sm italic" style="width: 88%"></div>
                        </div>
                    </div>
                    <div class="space-y-3 text-right">
                        <div class="text-[8px] text-emerald-400 font-black uppercase tracking-widest shadow-sm italic shadow-sm leading-none italic shadow-sm italic">Nutrient Density</div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm italic leading-none">
                            <div class="h-full bg-emerald-400 animate-[pulse_3s_infinite]" style="width: 72%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Harvest Stats Overlay -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-20 shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm">
                <div class="bg-emerald-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-emerald-500/20 shadow-sm italic shadow-sm italic shadow-sm">
                    <span class="text-[9px] text-emerald-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none shadow-sm">Annual Yield</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter shadow-sm" x-text="annualYield + ' Tons'"></span>
                </div>
                <div class="bg-emerald-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-emerald-500/20 text-center shadow-sm italic shadow-sm italic shadow-sm">
                    <span class="text-[9px] text-emerald-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic shadow-sm">H2O Recovery</span>
                    <span class="text-2xl text-emerald-400 font-black italic tracking-tighter shadow-sm" x-text="waterEfficiency"></span>
                </div>
                <div class="bg-emerald-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-emerald-500/20 text-right shadow-sm italic shadow-sm italic">
                    <span class="text-[9px] text-emerald-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none">CO2 Capture</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter shadow-sm" x-text="co2Enrichment ? 'High' : 'Off'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Agritech Configuration -->
        <div class="bg-white/[0.02] p-8 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden group/controls shadow-sm">
            <!-- Organic Pattern Background -->
            <div class="absolute inset-x-0 bottom-0 top-1/2 opacity-[0.03] pointer-events-none text-emerald-500 overflow-hidden text-[8px] font-mono leading-none shadow-sm">
                <template x-for="i in 20">
                    <div x-text="'PHYTO' + Math.random().toString(36).substring(5)" class="inline-block px-1 shadow-sm leading-none shadow-sm italic shadow-sm"></div>
                </template>
            </div>

            <div class="mb-14 relative z-10 shadow-sm leading-none shadow-sm italic shadow-sm">
                <div class="inline-block px-4 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 mb-6 shadow-xl leading-none italic tracking-tighter shadow-sm">
                    <span class="text-[10px] text-emerald-400 font-black uppercase tracking-[0.3em] shadow-sm leading-none italic shadow-sm leading-none italic shadow-sm italic leading-none">Autonomous Farming Vertical</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none italic tracking-tighter shadow-sm leading-none italic shadow-sm">Aero-Phyto Tower</h3>
                <p class="text-[12px] text-slate-500 font-extrabold uppercase tracking-widest mt-6 opacity-80 leading-relaxed italic tracking-tighter leading-none shadow-sm italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm">AI-Driven Controlled Environment Agriculture (CEA)
                with Real-Time Nutrient Optimization v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-10 px-4 shadow-sm leading-none shadow-sm">
                <!-- Tower Count Slider -->
                <div class="space-y-6 shadow-sm leading-none shadow-sm">
                    <div class="flex justify-between items-end mb-2 pr-2 shadow-sm leading-none shadow-sm">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic shadow-sm">Cultivation Towers</span>
                        <span class="text-3xl font-black text-emerald-500 italic tracking-tighter shadow-sm leading-none italic shadow-sm" x-text="towers"></span>
                    </div>
                    <div class="relative py-3 shadow-sm leading-none shadow-sm italic shadow-sm">
                        <input type="range" x-model="towers" min="4" max="48" step="4" 
                               class="w-full h-2.5 bg-slate-900 rounded-full appearance-none cursor-pointer accent-emerald-500 shadow-inner shadow-sm leading-none shadow-sm">
                    </div>
                </div>

                <!-- Strategic Toggles -->
                <div class="grid grid-cols-1 gap-5 shadow-sm leading-none shadow-sm italic shadow-sm">
                    <button @click="fullAutomation = !fullAutomation" 
                            class="flex items-center justify-between p-7 rounded-[2.5rem] border transition-all duration-500 relative group/btn overflow-hidden shadow-2xl shadow-sm leading-none shadow-sm"
                            :class="fullAutomation ? 'bg-emerald-500/10 border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-sm leading-none shadow-sm' : 'bg-white/5 border-white/10 opacity-70 hover:opacity-100 shadow-sm shadow-sm italic shadow-sm '">
                        <div class="flex items-center space-x-6 relative z-10 shadow-sm leading-none shadow-sm italic shadow-sm">
                            <div class="w-16 h-16 rounded-3xl flex items-center justify-center transition-all shadow-xl border border-white/5 shadow-sm shadow-sm leading-none shadow-sm" :class="fullAutomation ? 'bg-emerald-600 text-slate-950 scale-110 -rotate-3' : 'bg-slate-800 text-white/20 shadow-sm shadow-sm '">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 shadow-sm shadow-sm leading-none shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor shadow-sm leading-none shadow-sm">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z shadow-sm shadow-sm leading-none shadow-sm " />
                                </svg>
                            </div>
                            <div class="text-left leading-none shadow-sm shadow-sm italic shadow-sm leading-none shadow-sm">
                                <span class="text-lg font-black block text-white italic leading-none tracking-tighter shadow-sm leading-none shadow-sm italic shadow-sm italic shadow-sm shadow-sm shadow-sm">Full AI-Robotic Harvest</span>
                                <span class="text-[10px] text-emerald-400 uppercase font-black tracking-widest block mt-3 italic tracking-tighter shadow-sm leading-none italic shadow-sm leading-none shadow-sm leading-none italic shadow-sm">Autonomous Nutrient Mix Calibration active shadow-sm italic shadow-sm</span>
                            </div>
                        </div>
                    </button>
                    
                    <div class="grid grid-cols-2 gap-5 leading-none px-1 shadow-sm italic shadow-sm">
                        <div class="space-y-4 leading-none shadow-sm shadow-sm shadow-sm italic shadow-sm">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-2 italic shadow-sm shadow-sm shadow-sm">Spectral Core</span>
                            <button @click="spectralOptimization = !spectralOptimization" 
                                    class="w-full py-3.5 rounded-xl text-[10px] font-black uppercase transition-all shadow-sm shadow-sm italic shadow-sm leading-none shadow-sm italic" 
                                    :class="spectralOptimization ? 'bg-emerald-600 text-white shadow-lg shadow-sm leading-none shadow-sm' : 'bg-white/5 text-slate-500 shadow-sm shadow-sm shadow-sm italic leading-none ' shadow-sm ">
                                Quantum-Bloom UV
                            </button>
                        </div>
                        <div class="space-y-4 leading-none text-right shadow-sm shadow-sm shadow-sm">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pr-2 italic shadow-sm shadow-sm leading-none italic shadow-sm">CO2 Core</span>
                            <button @click="co2Enrichment = !co2Enrichment" 
                                    class="w-full py-3.5 rounded-xl text-[10px] font-black uppercase transition-all shadow-sm leading-none shadow-sm" 
                                    :class="co2Enrichment ? 'bg-emerald-600 text-white shadow-lg shadow-sm leading-none shadow-sm' : 'bg-white/5 text-slate-500 shadow-sm leading-none shadow-sm' shadow-sm shadow-sm leading-none shadow-sm">
                                Atmo-Rich Injection
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-16 p-12 bg-black rounded-[3.5rem] border border-white/10 shadow-3xl group/confirm overflow-hidden relative shadow-sm italic shadow-sm">
                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-emerald-500 to-transparent top-0 animate-[pulse_2s_infinite] shadow-sm leading-none shadow-sm"></div>
                
                <div class="flex items-center justify-between mb-10 relative z-10 leading-none shadow-sm shadow-sm italic shadow-sm shadow-sm">
                    <div>
                        <span class="text-[12px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-4 leading-none italic tracking-tighter leading-none italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm italic leading-none italic shadow-sm leading-none shadow-sm italic shadow-sm ">Aero-Farm Cluster CAPEX</span>
                        <span class="text-6xl font-black text-emerald-500 italic tracking-tighter shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm shadow-sm leading-none shadow-sm" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-10 shadow-sm italic shadow-sm">
                    <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-7 rounded-2xl transition-all shadow-[0_30px_60px_rgba(16,185,129,0.4)] uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-[0.98] group/btnprimary relative overflow-hidden shadow-sm italic shadow-sm shadow-sm">
                        <span class="relative z-10 shadow-sm leading-none shadow-sm">Deploy Vertical Phyto-Node</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover/btnprimary:translate-x-1.5 transition-transform relative z-10 shadow-sm italic shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor shadow-sm leading-none shadow-sm shadow-sm italic">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8 shadow-sm italic " />
                        </svg>
                    </button>
                    <p class="text-center text-[9px] text-slate-500 uppercase font-black italic tracking-widest mt-2 shadow-sm leading-none italic shadow-sm">Biosphere Sync Protocol Ready (Phyto-Core v.4.2) shadow-sm leading-none shadow-sm italic </p>
                </div>
            </div>
        </div>
    </div>
</div>
