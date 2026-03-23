@php
    declare(strict_types=1);
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
@endphp

<div x-data="{
    hullMaterial: 'carbon',
    length: 12,
    decks: 2,
    garageForSub: true,
    interiorStyle: 'minimalist',
    engineState: 'idle',
    showTelemetry: true,
    
    get totalArea() {
        return this.length * 4 * this.decks;
    },
    
    get baseCost() {
        let multiplier = this.hullMaterial === 'carbon' ? 1500000 : 800000;
        let cost = this.totalArea * multiplier;
        if (this.garageForSub) cost += 4500000;
        return cost;
    },

    formatValue(val) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(56, 189, 248, 0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[850px] relative z-10">
        
        <!-- Visual Section: Yacht Exterior/Interior Simulation -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner">
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-30">
                <div class="absolute inset-inline-0 h-[2px] bg-sky-500/50 blur-sm animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-8 left-8 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-lg border-l-2 border-sky-500 shadow-lg">
                    <div class="w-2 h-2 bg-sky-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(14,165,233,0.8)]"></div>
                    <span class="text-[10px] text-sky-400 font-black uppercase tracking-[0.2em] italic">Nautical-Core / v.2026.4</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-3 py-1 rounded text-[8px] text-slate-500 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Engine Telemetry HUD (Right) -->
            <div class="absolute top-8 right-8 z-20 hidden md:flex flex-col space-y-4 text-right">
                <div x-show="showTelemetry" class="space-y-4">
                    <div class="p-3 bg-black/40 backdrop-blur-md rounded-xl border border-white/5">
                        <span class="text-[8px] text-sky-500 font-black uppercase tracking-widest block mb-1">Hull Integrity</span>
                        <div class="w-24 h-1 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-sky-500" :style="`inline-size: ${hullMaterial === 'carbon' ? '98%' : '85%'}`"></div>
                        </div>
                    </div>
                    <div class="p-3 bg-black/40 backdrop-blur-md rounded-xl border border-white/5">
                        <span class="text-[8px] text-sky-500 font-black uppercase tracking-widest block mb-1">Nautical Stability</span>
                        <div class="flex items-center justify-end space-x-1">
                            <template x-for="i in 5">
                                <div class="w-1 h-3 rounded-sm" :class="i <= 4 ? 'bg-sky-500' : 'bg-slate-700'"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden group/viz">
                <!-- Water Surface Reflection -->
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(14,165,233,0.15)_0%,transparent_60%)] animate-pulse"></div>
                
                <!-- Crosshair HUD -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-20 transition-opacity group-hover/viz:opacity-40">
                    <div class="w-64 h-64 border border-sky-500/30 rounded-full border-dashed animate-[spin_20s_linear_infinite]"></div>
                    <div class="absolute w-full h-[1px] bg-sky-500/20"></div>
                    <div class="absolute w-[1px] h-full bg-sky-500/20"></div>
                </div>

                <svg viewBox="0 0 400 240" class="w-full h-full drop-shadow-[0_0_60px_rgba(14,165,233,0.3)] filter contrast-125" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <linearGradient id="hullGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:rgba(14,165,233,0.4);stop-opacity:1" />
                            <stop offset="100%" style="stop-color:rgba(14,165,233,0);stop-opacity:0" />
                        </linearGradient>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="1.5" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>

                    <!-- Motion Waves -->
                    <path d="M 0 190 Q 50 185 100 190 T 200 190 T 300 190 T 400 190" fill="none" stroke="rgba(14,165,233,0.2)" stroke-width="1" class="animate-[dash_10s_linear_infinite]" stroke-dasharray="2,4" />

                    <!-- Yacht Hull -->
                    <path :d="`M 50 180 L 350 180 L 385 145 L 80 145 Z`" 
                          :fill="hullMaterial === 'carbon' ? '#0f172a' : '#f8fafc'" 
                          stroke="rgba(14,165,233,0.6)" stroke-width="2.5" class="transition-all duration-700" 
                          :filter="hullMaterial === 'carbon' ? 'url(#glow)' : ''" />
                    
                    <!-- Decks -->
                    <template x-for="deck in parseInt(decks)">
                        <rect :x="100 + (deck * 12)" :y="145 - (deck * 28)" :width="240 - (deck * 25)" height="28" 
                              fill="rgba(14,165,233,0.1)" stroke="rgba(14,165,233,0.5)" stroke-width="1.5" 
                              class="transition-all duration-700 hover:fill-sky-500/20 cursor-crosshair" />
                    </template>

                    <!-- Tech Lines -->
                    <line x1="80" y1="145" x2="385" y2="145" stroke="rgba(14,165,233,0.8)" stroke-width="0.5" stroke-dasharray="5,5" />

                    <!-- Submarine Garage Indicator -->
                    <g x-show="garageForSub" class="animate-pulse">
                        <path d="M 320 185 L 360 185 L 350 205 L 330 205 Z" fill="#0ea5e9" opacity="0.8" />
                        <circle cx="340" cy="195" r="2" fill="white" class="animate-ping" />
                    </g>

                    <!-- Interior Scan Accent -->
                    <circle :cx="180 + (length * 0.5)" cy="130" r="3" fill="#0ea5e9" class="animate-pulse" />
                </svg>
            </div>

            <!-- Marine Specs HUD Footer -->
            <div class="p-8 grid grid-cols-3 gap-1 relative z-10 bg-black/20 backdrop-blur-sm border-t border-white/5">
                <div class="bg-black/40 p-4 rounded-2xl border-b-2 border-sky-500/30">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 leading-none">LOA / Infrastructure</span>
                    <span class="text-xl text-white font-black italic tracking-tighter" x-text="length + 'M / CLASS-01'"></span>
                </div>
                <div class="bg-black/40 p-4 rounded-2xl border-b-2 border-sky-500/30">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 leading-none">Modular Volume</span>
                    <span class="text-xl text-white font-black italic tracking-tighter" x-text="totalArea + ' m²'"></span>
                </div>
                <div class="bg-black/40 p-4 rounded-2xl border-b-2 border-sky-500/30">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 leading-none">Hull Composition</span>
                    <span class="text-xl text-white font-black italic tracking-tighter uppercase" x-text="hullMaterial"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Luxury Personalization -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden backdrop-blur-md">
            
            <div class="mb-12 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">Nautical-Core</h3>
                        <p class="text-[10px] text-sky-500 font-extrabold uppercase tracking-[0.3em] mt-3 opacity-80 italic flex items-center space-x-2">
                             <span class="w-8 h-[1px] bg-sky-500"></span>
                             <span>Elite Vessel Fabrication</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-[8px] text-slate-500 uppercase font-black tracking-[0.2em] block">Status</span>
                        <span class="text-xs text-green-400 font-black uppercase tracking-tighter animate-pulse">Synced</span>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-10">
                <!-- Hull Material -->
                <div class="p-6 bg-black/20 rounded-[2rem] border border-white/5 relative overflow-hidden group/opt transition-all hover:bg-black/40">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-[10px] text-sky-400 font-black uppercase tracking-[0.2em]">01. Primary Structure</span>
                        <span class="w-10 h-[2px] bg-sky-500/30"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="hullMaterial = 'carbon'" 
                                :class="hullMaterial === 'carbon' ? 'bg-sky-500 text-white shadow-[0_0_20px_rgba(14,165,233,0.4)]' : 'bg-white/5 text-slate-400 hover:bg-white/10'"
                                class="py-5 rounded-2xl font-black italic uppercase text-xs transition-all tracking-tighter border border-white/5">Liquid Carbon-Graphite</button>
                        <button @click="hullMaterial = 'titanium'" 
                                :class="hullMaterial === 'titanium' ? 'bg-sky-500 text-white shadow-[0_0_20px_rgba(14,165,233,0.4)]' : 'bg-white/5 text-slate-400 hover:bg-white/10'"
                                class="py-5 rounded-2xl font-black italic uppercase text-xs transition-all tracking-tighter border border-white/5">Neural Titanium Ally</button>
                    </div>
                </div>

                <!-- Length & Decks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none">Hull LOA / Meters</span>
                            <span class="text-2xl font-black text-sky-400 italic leading-none" x-text="length"></span>
                        </div>
                        <div class="relative py-2">
                            <input type="range" x-model="length" min="10" max="60" step="2" 
                               class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-sky-500 transition-all hover:accent-sky-400">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2 border-l-2 border-white/5 pl-6">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none">Deck Hierarchy</span>
                            <span class="text-2xl font-black text-sky-400 italic leading-none" x-text="decks"></span>
                        </div>
                        <div class="relative py-2">
                            <input type="range" x-model="decks" min="1" max="5" step="1" 
                               class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-sky-500 transition-all hover:accent-sky-400">
                        </div>
                    </div>
                </div>

                <!-- Custom Integration -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-6 bg-black/40 rounded-3xl border border-white/10 group/toggle hover:border-sky-500/50 transition-all">
                        <div class="flex space-x-4 items-center">
                            <div class="p-3 bg-sky-500/10 rounded-xl">
                                <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-base font-black italic block leading-none uppercase tracking-tighter">Submersible-Bay</span>
                                <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Multi-Node Hydro-Garage Integration</p>
                            </div>
                        </div>
                        <button @click="garageForSub = !garageForSub" 
                                class="w-14 h-7 rounded-full relative transition-all duration-300 shadow-inner" 
                                :class="garageForSub ? 'bg-sky-500 shadow-[0_0_15px_rgba(14,165,233,0.5)]' : 'bg-slate-800'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-300 shadow-lg" 
                                 :style="garageForSub ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="p-6 bg-black/20 rounded-[2rem] border border-white/5">
                        <span class="text-[9px] text-slate-500 uppercase font-black tracking-[0.2em] block mb-5 leading-none pl-1">Interior Optimization</span>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="style in ['minimalist', 'neo-deco', 'zen']">
                                <button @click="interiorStyle = style"
                                        :class="interiorStyle === style ? 'border-sky-500 text-sky-400 bg-sky-500/10 shadow-[0_0_15px_rgba(14,165,233,0.1)]' : 'border-white/5 text-slate-500 hover:bg-white/5'"
                                        class="py-3 border rounded-xl text-[10px] uppercase font-black italic transition-all"
                                        x-text="style"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total HUD -->
            <div class="mt-12 p-8 bg-sky-500 rounded-[2.5rem] shadow-[0_20px_50px_rgba(14,165,233,0.3)] relative overflow-hidden group/total">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.1)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_5s_infinite_linear]"></div>
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] font-black text-sky-950 uppercase tracking-[0.2em] block mb-1">Total Configuration Value</span>
                        <div class="text-4xl font-black text-slate-950 italic tracking-tighter" x-text="formatValue(baseCost)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-8 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl">
                        Acquire
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
            </div>

            <!-- Quotation -->
            <div class="mt-10 pt-8 border-t border-white/5">
                <div class="flex items-center justify-between mb-8">
                    <div class="w-full text-right">
                        <span class="text-[10px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-1">Estimated Net Value</span>
                        <span class="text-5xl font-black text-white italic tracking-tighter" x-text="formatValue(baseCost)"></span>
                    </div>
                </div>

                <button class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-black py-6 rounded-3xl shadow-[0_20px_40px_rgba(6,182,212,0.3)] transition-all flex items-center justify-center space-x-4 group overflow-hidden relative active:scale-95">
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <span class="text-xl italic uppercase tracking-tighter relative z-10">Initialize Marine Blueprint</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
