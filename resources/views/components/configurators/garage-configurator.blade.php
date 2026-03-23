@php
    declare(strict_types=1);
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
@endphp

<div x-data="{
    area: 36,
    shelfCount: 4,
    storageLevel: 'Pro',
    evCharger: false,
    pneumatic: false,
    lightingMode: 'full',
    showTelemetry: true,
    
    get totalCost() {
        let base = this.area * 4500;
        base += this.shelfCount * 12500;
        if (this.storageLevel === 'Pro') base += 85000;
        if (this.evCharger) base += 145000;
        if (this.pneumatic) base += 45000;
        return base;
    },

    formatValue(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid: Technical Drafting Style -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(148, 163, 184, 0.2) 1px, transparent 0); background-size: 50px 50px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[900px] relative z-10 font-sans uppercase">
        
        <!-- Visual Section: Vault-Core Hub Simulation -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner transition-all duration-700">
            
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-10">
                <div class="absolute inset-inline-0 h-[3px] bg-slate-400/50 blur-md animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-10 left-10 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-4 bg-black/60 backdrop-blur-2xl px-6 py-2.5 rounded-xl border-l-4 border-slate-500 shadow-lg">
                    <div class="w-2.5 h-2.5 bg-slate-500 rounded-full animate-pulse shadow-[0_0_12px_rgba(148,163,184,0.8)]"></div>
                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-[0.3em] font-black italic italic leading-none">Vault-Core / v.26.G</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-4 py-1.5 rounded-lg text-[8px] text-slate-500 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Technical Telemetry HUD -->
            <div class="absolute top-10 right-10 z-20 flex flex-col space-y-4 text-right italic font-black">
                <div x-show="showTelemetry" class="space-y-4">
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-slate-800/30">
                        <span class="text-[8px] text-slate-500 font-black uppercase tracking-widest block mb-1 font-black leading-none">Load Factor</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none" x-text="'350 kg / S'"></span>
                    </div>
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-slate-800/30">
                        <span class="text-[8px] text-slate-500 font-black uppercase tracking-widest block mb-1 font-black leading-none">Floor Matrix</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none text-slate-400" x-text="'Polyaspartic'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization: Industrial Drafting -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(148,163,184,0.1)_0%,transparent_60%)] animate-pulse"></div>
                
                <svg viewBox="0 0 600 600" class="w-full h-full drop-shadow-[0_0_80px_rgba(148,163,184,0.25)] transition-all duration-1000 filter brightness-110" preserveAspectRatio="xMidYMid meet">
                    <!-- Floor Grayscale Grid -->
                    <defs>
                        <pattern id="technicalGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect x="50" y="50" width="500" height="500" fill="url(#technicalGrid)" stroke="rgba(148,163,184,0.1)" stroke-width="2" />

                    <!-- Storage Array Silhouette -->
                    <g transform="translate(80, 100)" class="transition-all duration-500">
                        <template x-for="i in Math.min(shelfCount, 6)">
                            <g :transform="'translate(' + ((i-1)*70) + ', 0)'">
                                <rect width="50" height="350" fill="none" stroke="#64748b" stroke-width="1.5" stroke-dasharray="4,2" />
                                <template x-for="j in 5">
                                    <line x1="0" :y1="j*70" x2="50" :y2="j*70" stroke="#64748b" stroke-width="1" />
                                </template>
                            </g>
                        </template>
                    </g>

                    <!-- EV Charger HUD Node -->
                    <template x-if="evCharger">
                        <g transform="translate(480, 420)" class="transition-all duration-500">
                            <circle r="30" fill="none" stroke="#22c55e" stroke-width="2" stroke-dasharray="8,4" class="animate-spin" style="animation-duration: 10s" />
                            <circle r="20" fill="rgba(34,197,94,0.1)" stroke="#22c55e" stroke-width="1" />
                            <path d="M -5 -8 L 5 0 L -5 8" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" />
                        </g>
                    </template>

                    <!-- Pneumatic Line HUD -->
                    <template x-if="pneumatic">
                        <path d="M 50 100 L 550 100" fill="none" stroke="#475569" stroke-width="2" stroke-dasharray="10,10" />
                        <circle cx="50" cy="100" r="5" fill="#475569" />
                    </template>

                    <!-- Dimension Markers -->
                    <g transform="translate(50, 565)" opacity="0.4">
                        <path d="M 0 -5 L 0 5 M 500 -5 L 500 5 M 0 0 L 500 0" stroke="#64748b" stroke-width="1" />
                        <text x="250" y="20" fill="#64748b" font-size="10" font-family="monospace" text-anchor="middle" font-weight="black" x-text="area + ' m² PLAN'"></text>
                    </g>
                </svg>
            </div>

            <!-- Heavy Storage HUD Footer -->
            <div class="p-10 grid grid-cols-3 gap-1 relative z-10 bg-black/50 backdrop-blur-2xl border-t border-white/5 italic font-black italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-slate-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none">Storage Volume</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none" x-text="shelfCount * 4.5 + ' m³ / CAP' font-black"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-slate-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none">Pneumo Link</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none transition-colors" :class="pneumatic ? 'text-slate-400' : 'text-slate-600'" x-text="pneumatic ? 'ACTIVE' : 'STBY' font-black"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-slate-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none">System Load</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none" x-text="Math.floor(totalCost / 500000 * 100) + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Vault Architecture -->
        <div class="bg-slate-900/40 p-12 lg:p-16 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden backdrop-blur-3xl italic font-black italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
            
            <div class="mb-16 relative group/header font-black">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-5xl font-black text-white tracking-tighter uppercase leading-none group-hover/header:text-slate-400 transition-colors">Vault-Core</h3>
                        <p class="text-[10px] text-slate-500 font-extrabold uppercase tracking-[0.5em] mt-4 opacity-80 italic flex items-center space-x-3">
                             <span class="w-12 h-[1px] bg-slate-500 animate-pulse"></span>
                             <span>Industrial Space Tuner</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-12 overflow-y-auto pr-4 custom-scrollbar">
                
                <!-- Layout Parameters -->
                <div class="p-8 bg-black/40 rounded-[2.5rem] border border-white/5 relative overflow-hidden group/opt hover:bg-black/60 transition-all font-black uppercase">
                    <div class="flex justify-between items-center mb-8 pl-2 font-black italic leading-none">
                        <span class="text-[11px] text-slate-500 font-black uppercase tracking-[0.3em]">01. Grid Geometry</span>
                        <div class="w-16 h-[1px] bg-slate-500/30"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 font-black tracking-tighter italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">
                        <div class="space-y-6 italic font-black font-black">
                            <div class="flex justify-between items-end mb-4 font-black">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Floor Area</span>
                                <span class="text-3xl text-white tracking-tighter" x-text="area + ' m²'"></span>
                            </div>
                            <input type="range" x-model="area" min="15" max="250" step="5" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-slate-600">
                        </div>
                        <div class="space-y-6 italic font-black font-black font-black">
                            <div class="flex justify-between items-end mb-4 font-black italic">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Shelf Sections</span>
                                <span class="text-3xl text-white tracking-tighter font-black italic" x-text="shelfCount + ' UNITS'"></span>
                            </div>
                            <input type="range" x-model="shelfCount" min="2" max="20" step="1" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-slate-600">
                        </div>
                    </div>
                </div>

                <!-- Systems Selection -->
                <div class="grid grid-cols-2 gap-6 italic font-black uppercase">
                    <button @click="storageLevel = 'Pro'" 
                            :class="storageLevel === 'Pro' ? 'bg-slate-700/30 text-white border-slate-500 shadow-xl shadow-slate-900/50' : 'bg-black/40 text-slate-500 border-white/5 font-black italic leading-none uppercase leading-none'"
                            class="p-6 rounded-3xl border transition-all text-left space-y-3 font-black uppercase leading-none font-black whitespace-nowrap leading-none uppercase leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase underline leading-none uppercase leading-none font-black italic leading-none font-black font-black uppercase">
                        <span class="text-[9px] uppercase tracking-widest block font-black font-black uppercase leading-none">Tier-01 Solution</span>
                        <span class="text-xs font-black italic tracking-widest leading-none font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none uppercase leading-none uppercase leading-none lowercase italic font-black italic leading-none uppercase leading-none font-black italic leading-none uppercase leading-none">Professional Cabinet Link</span>
                    </button>
                    <button @click="storageLevel = 'Lite'" 
                            :class="storageLevel === 'Lite' ? 'bg-slate-700/30 text-white border-slate-500 shadow-xl shadow-slate-900/50' : 'bg-black/40 text-slate-500 border-white/5 font-black italic leading-none uppercase leading-none'"
                            class="p-6 rounded-3xl border transition-all text-left space-y-3 font-black uppercase leading-none font-black leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none uppercase leading-none uppercase leading-none lowercase italic font-black italic leading-none uppercase leading-none font-black italic leading-none uppercase leading-none">
                        <span class="text-[9px] uppercase tracking-widest block font-black font-black italic font-black font-black uppercase">Basic Grid</span>
                        <span class="text-xs font-black italic tracking-widest leading-none font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none lowercase italic font-black italic leading-none uppercase leading-none font-black italic leading-none uppercase leading-none uppercase leading-none uppercase leading-none font-black">Modular Mesh Rack Setup</span>
                    </button>
                </div>

                <!-- Heavy Systems Toggle -->
                <div class="space-y-6 italic font-black uppercase">
                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-slate-500/30 group/sys">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none h-4 uppercase font-black italic leading-none uppercase font-black leading-none italic font-black leading-none font-black italics font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none uppercase inline-flex items-center space-x-2">
                            <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center border border-green-500/20 group-hover/sys:bg-green-500/20 transition-all">
                                <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter">EV Charging Matrix</span>
                                <p class="text-[9px] text-slate-500 tracking-[.2em] mt-2 whitespace-nowrap leading-none font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none font-black italics font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none uppercase whitespace-nowrap leading-none transition-all duration-300">Tesla / ABB High-Speed 22kW Link</p>
                            </div>
                        </div>
                        <button @click="evCharger = !evCharger" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner" 
                                :class="evCharger ? 'bg-green-600 shadow-[0_0_20px_rgba(34,197,94,0.5)]' : 'bg-slate-800 font-black italic font-black'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl font-black italic font-black italic leading-none uppercase leading-none" 
                                 :style="evCharger ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-slate-500/30 group/sys font-black uppercase">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none">
                            <div class="w-14 h-14 bg-slate-500/10 rounded-2xl flex items-center justify-center border border-slate-500/20 group-hover/sys:bg-slate-500/20 transition-all font-black uppercase">
                                <svg class="w-7 h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter font-black font-black font-black uppercase">Pneumatic Air Grid</span>
                                <p class="text-[9px] text-slate-500 tracking-[.2em] mt-2 whitespace-nowrap font-black font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic font-black uppercase tracking-widest leading-none font-black font-black italic leading-none uppercase tracking-tighter leading-none italic underline leading-none uppercase font-black italic leading-none font-black font-black italic leading-none uppercase leading-none font-black leading-none uppercase leading-none">Compressed Line System / 10 Bar</p>
                            </div>
                        </div>
                        <button @click="pneumatic = !pneumatic" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner font-black uppercase" 
                                :class="pneumatic ? 'bg-slate-500 shadow-[0_0_20px_rgba(148,163,184,0.5)]' : 'bg-slate-800'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                                 :style="pneumatic ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Valuation HUD -->
            <div class="mt-16 p-12 bg-slate-600 rounded-[4rem] shadow-[0_40px_80px_rgba(0,0,0,0.5)] relative overflow-hidden group/total italic font-black italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.1)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_5s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-10 font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                    <div>
                        <span class="text-[11px] font-black text-slate-950 uppercase tracking-[0.5em] block mb-3 font-black font-black italic leading-none uppercase leading-none">Vault Configuration Cost</span>
                        <div class="text-6xl font-black text-slate-950 italic tracking-tighter bg-black/10 px-8 py-3 rounded-2xl shadow-inner font-black font-black transition-all duration-300" x-text="formatValue(totalCost)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-16 py-7 rounded-[2.5rem] font-black uppercase text-sm tracking-[0.4em] shadow-2xl hover:bg-slate-800 transition-all hover:scale-105 active:scale-95 italic font-black font-black underline-offset-8">
                        Lock Vault
                    </button>
                </div>
            </div>
            
            <p class="mt-10 text-[9px] text-slate-600 italic uppercase tracking-[0.6em] text-center leading-relaxed font-black font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase whitespace-nowrap leading-none transition-all duration-300">
                Precision Storage Solutions. Architectural Integrity Verified. Tier-01 Standard.
            </p>
        </div>
    </div>
</div>
