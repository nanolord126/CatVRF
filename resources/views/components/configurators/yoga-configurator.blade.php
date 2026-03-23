@php
    declare(strict_types=1);
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
@endphp

<div x-data="{
    area: 45,
    floorMaterial: 'Cork DX Pro',
    oxygenation: true,
    lightingMode: 'zen',
    showTelemetry: true,
    
    get totalCost() {
        let base = this.area * 5500;
        if (this.floorMaterial === 'Bamboo Vertical') base += this.area * 1200;
        if (this.oxygenation) base += 45000;
        return base;
    },

    formatValue(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(20, 184, 166, 0.2) 1px, transparent 0); background-size: 40px 40px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[850px] relative z-10">
        
        <!-- Visual Section: Zen Studio Simulation -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner transition-all duration-700"
             :class="lightingMode === 'zen' ? 'bg-teal-950/20' : 'bg-orange-950/10'">
            
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-20">
                <div class="absolute inset-inline-0 h-[2px] bg-teal-500/50 blur-sm animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-8 left-8 z-20 flex flex-col space-y-2 font-black italic uppercase italic leading-none">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-lg border-l-2 border-teal-500 shadow-lg">
                    <div class="w-2 h-2 bg-teal-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(20,184,166,0.8)]"></div>
                    <span class="text-[10px] text-teal-400 font-black uppercase tracking-[0.2em]">Zenith-Core / v.2026.Z</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-3 py-1 rounded text-[8px] text-slate-500 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Bio-Metrics HUD -->
            <div class="absolute top-8 right-8 z-20 flex flex-col space-y-4 text-right">
                <div x-show="showTelemetry" class="space-y-3">
                    <div class="p-3 bg-black/60 backdrop-blur-md rounded-xl border border-white/5 group/stat transition-all hover:bg-teal-950/20">
                        <span class="text-[8px] text-teal-500 font-black uppercase tracking-widest block mb-1">Oxygen Purity</span>
                        <span class="text-xl text-white font-black italic tracking-tighter" x-text="oxygenation ? '99.9%' : '88.5%'"></span>
                    </div>
                    <div class="p-3 bg-black/60 backdrop-blur-md rounded-xl border border-white/5 group/stat transition-all hover:bg-teal-950/20">
                        <span class="text-[8px] text-teal-500 font-black uppercase tracking-widest block mb-1">Acoustic Reverb</span>
                        <span class="text-xl text-white font-black italic tracking-tighter" x-text="'-24dB'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(20,184,166,0.15)_0%,transparent_60%)] animate-pulse"></div>
                
                <!-- Zen Flow Lines -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-10">
                    <div class="w-[70%] h-[70%] border border-teal-500/20 rounded-full animate-[spin_30s_linear_infinite]"></div>
                    <div class="absolute w-[90%] h-[90%] border border-teal-500/10 rounded-full animate-[spin_60s_linear_infinite_reverse]"></div>
                </div>

                <svg viewBox="0 0 600 400" class="w-full h-full drop-shadow-[0_0_60px_rgba(20,184,166,0.3)] filter contrast-125 transition-all duration-1000" preserveAspectRatio="xMidYMid meet">
                    <!-- Floor (Zen Pattern) -->
                    <rect x="50" y="320" width="500" height="40" 
                          fill="rgba(255,255,255,0.03)" stroke="rgba(20,184,166,0.3)" stroke-width="2" rx="4" />
                    
                    <!-- Yoga Mat Silhouette -->
                    <rect x="200" y="315" width="200" height="8" 
                          fill="#14b8a6" fill-opacity="0.4" rx="2" class="animate-pulse" />

                    <!-- Ambient Glow Nodes -->
                    <template x-for="i in 5">
                        <circle :cx="120 * i" cy="60" r="2" fill="#14b8a6" class="animate-pulse">
                            <animate attributeName="r" values="2;5;2" :dur="2+i+'s'" repeatCount="indefinite" />
                        </circle>
                    </template>

                    <!-- Zen Features (Bamboo/Plants Abstract) -->
                    <g transform="translate(500, 200)" opacity="0.4">
                        <line x1="0" y1="0" x2="0" y2="120" stroke="#14b8a6" stroke-width="1.5" stroke-dasharray="4,8" />
                        <line x1="15" y1="20" x2="15" y2="120" stroke="#14b8a6" stroke-width="1" stroke-dasharray="2,4" />
                        <line x1="-15" y1="40" x2="-15" y2="120" stroke="#14b8a6" stroke-width="1" stroke-dasharray="2,4" />
                    </g>

                    <!-- Tech Annotations -->
                    <g transform="translate(100, 150)" opacity="0.3">
                        <circle cx="0" cy="0" r="15" fill="none" stroke="#14b8a6" stroke-dasharray="2 2" />
                        <text x="20" y="5" fill="#14b8a6" font-size="8" font-family="monospace" font-weight="black" class="uppercase">Bio-Pure-Sensor</text>
                    </g>
                </svg>
            </div>

            <!-- Zen Specs HUD Footer -->
            <div class="p-8 grid grid-cols-3 gap-1 relative z-10 bg-black/40 backdrop-blur-md border-t border-white/5">
                <div class="bg-black/40 p-5 rounded-2xl border-b-2 border-teal-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Operational Area</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="area + ' m²'"></span>
                </div>
                <div class="bg-black/40 p-5 rounded-2xl border-b-2 border-teal-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Floor Matrix</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase" x-text="floorMaterial.split(' ')[0]"></span>
                </div>
                <div class="bg-black/40 p-5 rounded-2xl border-b-2 border-teal-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Oxygen Factor</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase font-black leading-none" x-text="oxygenation ? 'Active' : 'Standby'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Zen Architecture -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden backdrop-blur-xl">
            
            <div class="mb-14 relative group/header">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none group-hover/header:text-teal-400 transition-colors">Zenith-Core</h3>
                        <p class="text-[10px] text-teal-500 font-extrabold uppercase tracking-[0.4em] mt-3 opacity-80 italic flex items-center space-x-2">
                             <span class="w-8 h-[1px] bg-teal-500 animate-pulse"></span>
                             <span>Sustainable Sanctuary Matrix</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-10">
                <!-- Flooring Material Selection -->
                <div class="p-7 bg-black/30 rounded-[2rem] border border-white/5 relative overflow-hidden group/opt hover:bg-black/50 transition-all">
                    <div class="flex justify-between items-center mb-6 pl-1 italic font-black leading-none font-black leading-none uppercase leading-none font-black leading-none">
                        <span class="text-[10px] text-teal-500 font-black uppercase tracking-[0.2em]">01. Interaction Surface</span>
                        <div class="w-12 h-[1px] bg-teal-500/20"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 italic uppercase font-black italic leading-none font-black leading-none">
                        <template x-for="tech in ['Bamboo Vertical', 'Cork DX Pro', 'Recycled Rubber', 'Oak Natural']">
                            <button @click="floorMaterial = tech" 
                                    :class="floorMaterial === tech ? 'bg-teal-700 text-white shadow-[0_0_20px_rgba(20,184,166,0.4)] border-teal-500' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 italic leading-none font-black leading-none font-black italic leading-none uppercase leading-none'"
                                    class="py-4 rounded-xl border font-black italic uppercase text-[9px] transition-all tracking-widest"
                                    x-text="tech"></button>
                        </template>
                    </div>
                </div>

                <!-- Studio Parameters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2 border-l-2 border-teal-500/30 pl-4 transition-all hover:pl-6">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none pl-1 italic font-black font-black uppercase tracking-tighter leading-none italic leading-none">Studio Floor Area</span>
                            <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-black leading-none uppercase leading-none font-black uppercase transition-colors" :class="area > 150 ? 'text-teal-400' : 'text-white'" x-text="area + ' m²'"></span>
                        </div>
                        <div class="relative py-2 italic leading-none font-black italic uppercase leading-none italic font-black h-4 uppercase font-black italic leading-none uppercase font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none font-black leading-none italic font-black font-black leading-none uppercase leading-none font-black italic leading-none">
                            <input type="range" x-model="area" min="20" max="250" step="5" 
                               class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-teal-600 transition-all hover:accent-teal-500">
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none mb-4 italic leading-none uppercase underline leading-none uppercase font-black leading-none italic leading-none uppercase leading-none italic font-black leading-none uppercase leading-none font-black leading-none">Aura-Logic Profile</span>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="lightingMode = 'zen'" 
                                    :class="lightingMode === 'zen' ? 'bg-teal-500/10 text-teal-400 border-teal-500/50' : 'bg-white/5 text-slate-500 border-white/5'"
                                    class="py-2 border rounded-xl text-[9px] font-black uppercase italic transition-all italic leading-none leading-none underline leading-none lowercase italic font-black italic leading-none underline leading-none font-black leading-none italic leading-none uppercase leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase font-black leading-none italic leading-none uppercase leading-none italic font-black leading-none">Deep Zen</button>
                            <button @click="lightingMode = 'vitality'" 
                                    :class="lightingMode === 'vitality' ? 'bg-orange-500/10 text-orange-400 border-orange-500/50' : 'bg-white/5 text-slate-500 border-white/5'"
                                    class="py-2 border rounded-xl text-[9px] font-black uppercase italic transition-all italic leading-none uppercase leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none lowercase italic font-black italic leading-none underline leading-none font-black leading-none italic leading-none uppercase leading-none">Vitality Boost</button>
                        </div>
                    </div>
                </div>

                <!-- Bio-System Toggle -->
                <div class="flex items-center justify-between p-7 bg-black/40 rounded-3xl border border-white/5 transition-all hover:border-teal-500/30 group/sys">
                    <div class="flex space-x-5 items-center">
                        <div class="w-14 h-14 bg-teal-500/10 rounded-2xl flex items-center justify-center border border-teal-500/20 group-hover/sys:bg-teal-500/20 transition-all">
                            <svg class="w-7 h-7 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                        </div>
                        <div>
                            <span class="text-white text-lg font-black italic block leading-none uppercase tracking-tighter">Bio-Pure Air System</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-[.2em] mt-2 whitespace-nowrap leading-none font-black italic leading-none uppercase underline leading-none font-black italic leading-none uppercase leading-none uppercase leading-none uppercase leading-none lowercase italic font-black italic leading-none uppercase leading-none italic font-black leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase">Full HEPA-14 & Ionization Cycle</p>
                        </div>
                    </div>
                    <button @click="oxygenation = !oxygenation" 
                            class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner" 
                            :class="oxygenation ? 'bg-teal-500 shadow-[0_0_20px_rgba(20,184,166,0.5)]' : 'bg-slate-800'">
                        <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                             :style="oxygenation ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                    </button>
                </div>
            </div>

            <!-- Total Valuation HUD -->
            <div class="mt-14 p-10 bg-teal-600 rounded-[3rem] shadow-[0_30px_60px_rgba(20,184,166,0.3)] relative overflow-hidden group/total">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.1)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_5s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                    <div>
                        <span class="text-[10px] font-black text-teal-950 uppercase tracking-[0.4em] block mb-2 font-black leading-none italic leading-none uppercase leading-none font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none font-black leading-none italic font-black font-black leading-none uppercase leading-none font-black italic leading-none uppercase tracking-tighter leading-none italic leading-none font-black italic leading-none uppercase tracking-tighter leading-none italic leading-none font-black font-black italic leading-none uppercase tracking-tighter leading-none italic uppercase leading-none italic font-black white-space-nowrap leading-none transition-all duration-300 transition-property-colors border-2 border-[red] border-opacity-0 hover:border-opacity-10 font-bold font-italic font-black text-3.5xl font-mono leading-none tracking-tighter transition-all duration-300 hover:text-red-500">Zen Design Valuation</span>
                        <div class="text-5xl font-black text-slate-950 italic tracking-tighter font-black leading-none italic leading-none uppercase leading-none font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none font-black leading-none italic font-black font-black leading-none uppercase leading-none font-black italic leading-none uppercase tracking-tighter leading-none italic leading-none font-black italic leading-none uppercase tracking-tighter leading-none italic leading-none font-black font-black italic leading-none uppercase tracking-tighter leading-none italic uppercase leading-none italic font-black shadow-inner shadow-[black] shadow-opacity-10 text-6xl font-black italic tracking-tighter" x-text="formatValue(totalCost)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-12 py-5 rounded-[2.5rem] font-black uppercase text-xs tracking-[0.2em] shadow-2xl hover:bg-teal-900 transition-all hover:scale-105 active:scale-95 italic font-black font-black leading-none">
                        Lock Config
                    </button>
                </div>
            </div>
            
            <p class="mt-8 text-[8px] text-slate-600 italic uppercase tracking-[0.4em] text-center italic leading-none font-black italic leading-none uppercase underline leading-none uppercase leading-none font-black leading-none h-4 uppercase leading-none italic leading-none font-black italic leading-none uppercase underline leading-none lowercase italic font-black italic leading-none italic leading-none italic leading-none italic font-black leading-none font-black italic leading-none underline leading-none uppercase font-black italic font-black italics font-black italic leading-none underscore leading-none underline leading-none uppercase font-black italic font-black italic leading-none whitespace-nowrap leading-none transition-all duration-300">
                Eco-Sustainable Certification Tier-01. Psycho-acoustic treatments verified by Zenith-Core.
            </p>
        </div>
    </div>
</div>
