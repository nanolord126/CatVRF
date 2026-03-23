@php
    declare(strict_types=1);
    
    /** @var \App\Domains\ConstructionMaterials\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
    $correlationId = (string) \Illuminate\Support\Str::uuid();
@endphp

<div x-data="{
    length: 10,
    inline_size: 5,
    depth: 1.5,
    shellType: 'concrete',
    heating: true,
    lighting: true,
    counterflow: false,
    showTelemetry: true,
    
    get volume() {
        return Math.round(this.length * this.inline_size * this.depth);
    },
    
    get surfaceArea() {
        return Math.round(this.length * this.inline_size);
    },
    
    get totalPrice() {
        let basePrice = this.volume * 12500; // Базовая цена за куб
        if (this.shellType === 'overflow') basePrice *= 1.4;
        if (this.shellType === 'composite') basePrice *= 1.2;
        
        if (this.heating) basePrice += 185000;
        if (this.lighting) basePrice += 45000;
        if (this.counterflow) basePrice += 240000;
        
        return basePrice;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(6, 182, 212, 0.2) 1px, transparent 0); background-size: 50px 50px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[900px] relative z-10 font-sans uppercase">
        
        <!-- Visual Section: Hydra-Logic Hub -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner transition-all duration-700">
            
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-10">
                <div class="absolute inset-inline-0 h-[3px] bg-cyan-400/50 blur-md animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-10 left-10 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-4 bg-black/60 backdrop-blur-2xl px-6 py-2.5 rounded-xl border-l-4 border-cyan-500 shadow-lg">
                    <div class="w-2.5 h-2.5 bg-cyan-500 rounded-full animate-pulse shadow-[0_0_12px_rgba(6,182,212,0.8)]"></div>
                    <span class="text-[10px] text-cyan-400 font-black uppercase tracking-[0.3em] font-black italic italic leading-none">Hydra-Core / v.26.H</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-4 py-1.5 rounded-lg text-[8px] text-cyan-600 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-cyan-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Technical Telemetry HUD -->
            <div class="absolute top-10 right-10 z-20 flex flex-col space-y-4 text-right italic font-black">
                <div x-show="showTelemetry" class="space-y-4 font-black italic">
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-cyan-800/30">
                        <span class="text-[8px] text-cyan-600 font-black uppercase tracking-widest block mb-1 font-black leading-none">Hydro Static</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none" x-text="'99.8% OK'"></span>
                    </div>
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-cyan-800/30 font-black italic">
                        <span class="text-[8px] text-cyan-600 font-black uppercase tracking-widest block mb-1 font-black leading-none">Cl2 Level</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none text-cyan-400 font-black" x-text="'0.4 mg/l'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization: Hydra-Logic Plan -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(6,182,212,0.1)_0%,transparent_60%)] animate-pulse"></div>
                
                <svg viewBox="0 0 600 400" class="w-full h-full drop-shadow-[0_0_80px_rgba(6,182,212,0.25)] transition-all duration-1000 filter brightness-110" preserveAspectRatio="xMidYMid meet">
                    <!-- Pool Grayscale Grid -->
                    <defs>
                        <pattern id="poolGrid" width="60" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 60 0 L 0 0 0 40" fill="none" stroke="rgba(6,182,212,0.1)" stroke-width="0.5" />
                        </pattern>
                        <linearGradient id="waterGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#083344;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    
                    <rect x="50" y="50" width="500" height="300" fill="url(#poolGrid)" stroke="rgba(6,182,212,0.1)" stroke-width="2" />

                    <!-- Pool Shell Structure -->
                    <rect :x="300 - (inline_size * 20)" :y="200 - (length * 10)" :width="inline_size * 40" :height="length * 20" 
                          fill="rgba(6,182,212,0.05)" stroke="#0891b2" stroke-width="2" class="transition-all duration-700 font-black" />
                    
                    <!-- Water Volume Overlay -->
                    <rect :x="300 - (inline_size * 18)" :y="200 - (length * 9)" :width="inline_size * 36" :height="length * 18" 
                          fill="url(#waterGrad)" opacity="0.4" class="transition-all duration-700" />

                    <!-- Counterflow Node -->
                    <template x-if="counterflow">
                        <g :transform="'translate(300, ' + (200 - (length * 9)) + ')'">
                             <path d="M -40 0 Q 0 40 40 0" fill="none" stroke="white" stroke-width="1" stroke-dasharray="4,2">
                                 <animate attributeName="stroke-dashoffset" from="20" to="0" dur="1s" repeatCount="indefinite" />
                             </path>
                        </g>
                    </template>

                    <!-- Heating Ripples -->
                    <template x-if="heating">
                        <circle :cx="300" :cy="200" r="10" fill="none" stroke="white" stroke-width="0.5" opacity="0.3">
                            <animate attributeName="r" from="10" to="100" dur="4s" repeatCount="indefinite" />
                            <animate attributeName="opacity" from="0.3" to="0" dur="4s" repeatCount="indefinite" />
                        </circle>
                    </template>

                    <!-- Dimension Markers -->
                    <g :transform="'translate(' + (300 - (inline_size * 20)) + ', ' + (210 + (length * 10)) + ')'" opacity="0.4">
                        <path :d="'M 0 -5 L 0 5 M ' + (inline_size * 40) + ' -5 L ' + (inline_size * 40) + ' 5 M 0 0 L ' + (inline_size * 40) + ' 0'" stroke="#0891b2" stroke-width="1" />
                        <text :x="inline_size * 20" y="20" fill="#0891b2" font-size="10" font-family="monospace" text-anchor="middle" font-weight="black" x-text="inline_size + ' m'"></text>
                    </g>
                </svg>
            </div>

            <!-- Hydro Systems HUD Footer -->
            <div class="p-10 grid grid-cols-3 gap-1 relative z-10 bg-black/50 backdrop-blur-2xl border-t border-white/5 italic font-black uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-cyan-500/40 font-black">
                    <span class="text-[8px] text-cyan-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Total Volume</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none font-black font-black font-black uppercase underline leading-none uppercase leading-none uppercase leading-none font-black uppercase font-black italic leading-none uppercase leading-none font-black font-black uppercase" x-text="volume + ' m³ / HYDRA'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-cyan-500/40 font-black">
                    <span class="text-[8px] text-cyan-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Thermal Hub</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none transition-colors" :class="heating ? 'text-cyan-400 font-black underline' : 'text-slate-600'" x-text="heating ? 'ACTIVE' : 'STBY'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-cyan-500/40 font-black">
                    <span class="text-[8px] text-cyan-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Hydro Load</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none font-black italic" x-text="Math.floor(totalPrice / 3000000 * 100) + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Hydro Architecture -->
        <div class="bg-cyan-950/10 p-12 lg:p-16 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden backdrop-blur-3xl italic font-black">
            
            <div class="mb-16 relative group/header font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                <div class="flex items-center justify-between font-black uppercase tracking-tighter italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">
                    <div>
                        <h3 class="text-5xl font-black text-white tracking-tighter uppercase leading-none group-hover/header:text-cyan-400 transition-colors font-black flex items-center space-x-4">
                            <span>Hydra-Core</span>
                        </h3>
                        <p class="text-[10px] text-cyan-600 font-extrabold uppercase tracking-[0.5em] mt-4 opacity-80 italic flex items-center space-x-3">
                             <span class="w-12 h-[1px] bg-cyan-500 animate-pulse"></span>
                             <span>Industrial Flow Tuner</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-12 overflow-y-auto pr-4 custom-scrollbar font-black uppercase leading-none font-black bg-black/5 p-4 rounded-3xl underline decoration-cyan-500/30 font-black italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                
                <!-- Circulatory Parameters -->
                <div class="p-8 bg-black/40 rounded-[2.5rem] border border-white/5 relative overflow-hidden group/opt hover:bg-black/60 transition-all font-black uppercase underline-offset-8">
                    <div class="flex justify-between items-center mb-8 pl-2 font-black italic leading-none">
                        <span class="text-[11px] text-cyan-600 font-black uppercase tracking-[0.3em] font-black italic underline leading-none uppercase tracking-[0.2em] font-black italic leading-none font-black leading-none">01. Circulation Hub</span>
                        <div class="w-16 h-[1px] bg-cyan-500/30"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 font-black uppercase tracking-tighter italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">
                        <div class="space-y-6">
                            <div class="flex justify-between items-end mb-4 font-black">
                                <span class="text-[10px] text-cyan-600 uppercase font-black tracking-widest font-black italic">Long-Axis (M)</span>
                                <span class="text-3xl text-white tracking-tighter font-black" x-text="length + ' M'"></span>
                            </div>
                            <input type="range" x-model="length" min="3" max="25" step="1" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-600">
                        </div>
                        <div class="space-y-6">
                            <div class="flex justify-between items-end mb-4 font-black">
                                <span class="text-[10px] text-cyan-600 uppercase font-black tracking-widest font-black italics">Cross-Axis (M)</span>
                                <span class="text-3xl text-white tracking-tighter font-black italic font-black" x-text="inline_size + ' M'"></span>
                            </div>
                            <input type="range" x-model="inline_size" min="2" max="15" step="1" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-600">
                        </div>
                    </div>
                </div>

                <!-- Shell Configuration -->
                <div class="grid grid-cols-3 gap-4 font-black uppercase">
                    <template x-for="type in ['concrete', 'overflow', 'composite']">
                        <button @click="shellType = type" 
                                :class="shellType === type ? 'bg-cyan-700/30 text-white border-cyan-500 shadow-xl shadow-cyan-900/50 underline' : 'bg-black/40 text-slate-500 border-white/5 font-black italic leading-none uppercase leading-none'"
                                class="p-4 rounded-3xl border transition-all text-xs font-black uppercase tracking-widest text-center" x-text="type">
                        </button>
                    </template>
                </div>

                <!-- Hydro Systems Toggle -->
                <div class="space-y-6 font-black uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none">
                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-cyan-500/30 group/sys font-black uppercase">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none">
                            <div class="w-14 h-14 bg-cyan-500/10 rounded-2xl flex items-center justify-center border border-cyan-500/20 group-hover/sys:bg-cyan-500/20 transition-all font-black uppercase">
                                <svg class="w-7 h-7 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter font-black font-black uppercase leading-none">Heat Pump Matrix</span>
                                <p class="text-[9px] text-cyan-600 tracking-[.2em] mt-2 whitespace-nowrap font-black font-black italic leading-none uppercase underline leading-none uppercase font-black leading-none italic font-black font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none">Thermal 18kW Quantum Link</p>
                            </div>
                        </div>
                        <button @click="heating = !heating" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner font-black uppercase" 
                                :class="heating ? 'bg-cyan-600 shadow-[0_0_20px_rgba(6,182,212,0.5)]' : 'bg-slate-800 font-black italic leading-none uppercase leading-none font-black italic leading-none font-black underline decoration-cyan-500/30 font-black italic leading-none uppercase'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                                 :style="heating ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-cyan-500/30 group/sys font-black uppercase flex items-center space-x-2">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none">
                            <div class="w-14 h-14 bg-cyan-500/10 rounded-2xl flex items-center justify-center border border-cyan-500/20 group-hover/sys:bg-cyan-500/20 transition-all font-black uppercase inline-flex items-center space-x-2">
                                <svg class="w-7 h-7 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter font-black font-black uppercase leading-none">Turbine Flow Engine</span>
                                <p class="text-[9px] text-cyan-600 tracking-[.2em] mt-2 whitespace-nowrap font-black font-black italic leading-none uppercase underline leading-none uppercase font-black leading-none italic font-black font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none">Endless Pool Tech v.4.0</p>
                            </div>
                        </div>
                        <button @click="counterflow = !counterflow" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner" 
                                :class="counterflow ? 'bg-cyan-600 shadow-[0_0_20px_rgba(6,182,212,0.5)]' : 'bg-slate-800 font-black italic font-black'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                                 :style="counterflow ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Valuation HUD -->
            <div class="mt-16 p-12 bg-cyan-600 rounded-[4rem] shadow-[0_40px_80px_rgba(0,0,0,0.5)] relative overflow-hidden group/total font-black uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.2)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_5s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-10 font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                    <div>
                        <span class="text-[11px] font-black text-slate-950 uppercase tracking-[0.5em] block mb-3 font-black underline italic">Hydro-Capital Configuration</span>
                        <div class="text-6xl font-black text-slate-950 italic tracking-tighter bg-black/10 px-8 py-3 rounded-2xl shadow-inner font-black underline italic transition-all duration-300" x-text="formatPrice(totalPrice)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-16 py-7 rounded-[2.5rem] font-black uppercase text-sm tracking-[0.4em] shadow-2xl hover:bg-slate-800 transition-all hover:scale-105 active:scale-95 italic font-black underline leading-none">
                        Lock Hydro-Logic
                    </button>
                </div>
            </div>
            
            <p class="mt-10 text-[9px] text-cyan-800 italic uppercase tracking-[0.6em] text-center leading-relaxed font-black underline uppercase whitespace-nowrap leading-none transition-all duration-300">
                Precision Hydro Engineering. Structural Integrity Verified. Hydra-Core v.26.H Standard.
            </p>
        </div>
    </div>
</div>
