@php
    declare(strict_types=1);
@endphp

<div x-data="{
    depthMeters: 15,
    capsuleLevels: 3,
    scubaDock: true,
    viewingDome: 'Triple-Concave',
    atmoScrubbing: true,
    hydroEnergy: true,
    
    get totalArea() {
        return this.capsuleLevels * 120;
    },
    
    get pressureResistance() {
        return (this.depthMeters * 0.1 + 1).toFixed(1);
    },

    get estimatedCost() {
        let base = this.capsuleLevels * 75000000;
        base += this.depthMeters * 1500000;
        if (this.scubaDock) base += 12000000;
        if (this.hydroEnergy) base += 25000000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px]">
        
        <!-- Visual: Deep Sea Habitat Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl">
            <!-- HUD -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/80 backdrop-blur-2xl px-5 py-2.5 rounded-full border border-cyan-500/40 shadow-[0_0_40px_rgba(6,182,212,0.2)]">
                    <div class="w-2.5 h-2.5 bg-cyan-500 rounded-full animate-[ping_2s_linear_infinite]"></div>
                    <span class="text-[10px] text-cyan-400 font-black uppercase tracking-[0.3em] italic tracking-tighter leading-none">Abyssal-Core v.7.0 Sealed</span>
                </div>
            </div>

            <!-- Visualization: Underwater Habitat Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_100%,rgba(6,182,212,0.15)_0%,transparent_80%)]">
                <!-- Marine Bubbles Particles -->
                <div class="absolute inset-0 opacity-20 pointer-events-none">
                    <template x-for="i in 25">
                        <div class="absolute w-2 h-2 bg-cyan-400/30 rounded-full animate-[bounce_3s_infinite]" 
                             :style="`bottom: 0; left: ${Math.random()*100}%; animation-delay: ${Math.random()*5}s; animation-duration: ${Math.random()*10+5}s` shadow-sm"></div>
                    </template>
                </div>

                <svg viewBox="0 0 400 400" class="w-full h-full drop-shadow-[0_0_120px_rgba(6,182,212,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Habitat Structure -->
                    <template x-for="i in parseInt(capsuleLevels)">
                        <g :transform="`translate(0, ${250 - (i-1)*70})` shadow-sm">
                            <rect x="120" y="0" width="160" height="60" rx="30" fill="rgba(255,255,255,0.03)" stroke="#06b6d4" stroke-width="2" class="opacity-60" />
                            <circle x="150" y="30" r="10" fill="rgba(6,182,212,0.2)" stroke="#06b6d4" stroke-width="1" />
                        </g>
                    </template>

                    <!-- Viewing Dome -->
                    <path d="M 120 110 Q 200 20 280 110" fill="rgba(255,255,255,0.05)" stroke="#06b6d4" stroke-width="3" x-show="viewingDome" class="transition-all duration-1000 shadow-sm" />
                    
                    <!-- Support Girders -->
                    <line x1="120" y1="310" x2="120" y2="400" stroke="#06b6d4" stroke-width="4" stroke-dasharray="10 5" class="opacity-20 shadow-sm" />
                    <line x1="280" y1="310" x2="280" y2="400" stroke="#06b6d4" stroke-width="4" stroke-dasharray="10 5" class="opacity-20 shadow-sm" />
                </svg>

                <!-- Environmental Telemetry -->
                <div class="absolute bottom-16 inset-x-12 grid grid-cols-2 gap-8 leading-none px-4 shadow-sm italic shadow-sm leading-none italic shadow-sm">
                    <div class="space-y-3 shadow-sm italic leading-none shadow-sm leading-none italic shadow-sm">
                        <div class="text-[8px] text-cyan-400 font-black uppercase tracking-widest leading-none italic shadow-sm italic shadow-sm">Seal Integrity</div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm italic">
                            <div class="h-full bg-cyan-500 animate-[pulse_2s_infinite] shadow-sm italic" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="space-y-3 text-right">
                        <div class="text-[8px] text-cyan-400 font-black uppercase tracking-widest shadow-sm italic shadow-sm leading-none italic shadow-sm italic">O2 Saturation</div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm italic leading-none">
                            <div class="h-full bg-cyan-400 animate-[pulse_3s_infinite]" style="width: 96%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perf Stats Overlay -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-20 shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm italic shadow-sm">
                <div class="bg-cyan-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-cyan-500/20 shadow-sm italic shadow-sm italic shadow-sm">
                    <span class="text-[9px] text-cyan-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none shadow-sm">Abyssal Area</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter shadow-sm" x-text="totalArea + ' m²'"></span>
                </div>
                <div class="bg-cyan-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-cyan-500/20 text-center shadow-sm italic shadow-sm italic shadow-sm">
                    <span class="text-[9px] text-cyan-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic shadow-sm">Barometric Load</span>
                    <span class="text-2xl text-cyan-400 font-black italic tracking-tighter shadow-sm" x-text="pressureResistance + ' ATM'"></span>
                </div>
                <div class="bg-cyan-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-cyan-500/20 text-right shadow-sm italic shadow-sm italic">
                    <span class="text-[9px] text-cyan-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none">Max Depth</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter shadow-sm" x-text="depthMeters + 'm'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Deep Sea Configuration -->
        <div class="bg-white/[0.02] p-8 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden group/controls shadow-sm">
            <div class="mb-14 relative z-10 shadow-sm leading-none shadow-sm italic shadow-sm">
                <div class="inline-block px-4 py-1.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 mb-6 shadow-xl leading-none italic tracking-tighter shadow-sm">
                    <span class="text-[10px] text-cyan-400 font-black uppercase tracking-[0.3em] shadow-sm leading-none italic shadow-sm leading-none italic shadow-sm italic leading-none">Abyssal Vertical</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none italic tracking-tighter shadow-sm leading-none italic shadow-sm">Deep-Sea Habitat</h3>
                <p class="text-[12px] text-slate-500 font-extrabold uppercase tracking-widest mt-6 opacity-80 leading-relaxed italic tracking-tighter leading-none shadow-sm italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm">Sub-Aquatic High-Density Living Modules with
                Hyperbaric Life-Support & Hydro-Core Energy v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-10 px-4 shadow-sm leading-none shadow-sm">
                <!-- Depth Slider -->
                <div class="space-y-6 shadow-sm leading-none shadow-sm">
                    <div class="flex justify-between items-end mb-2 pr-2 shadow-sm leading-none shadow-sm">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic shadow-sm">Deployment Depth</span>
                        <span class="text-3xl font-black text-cyan-500 italic tracking-tighter shadow-sm leading-none italic shadow-sm" x-text="depthMeters + 'm'"></span>
                    </div>
                    <div class="relative py-3 shadow-sm leading-none shadow-sm italic shadow-sm">
                        <input type="range" x-model="depthMeters" min="5" max="100" step="5" 
                               class="w-full h-2.5 bg-slate-900 rounded-full appearance-none cursor-pointer accent-cyan-500 shadow-inner shadow-sm leading-none shadow-sm">
                    </div>
                </div>

                <!-- Strategic Toggles -->
                <div class="grid grid-cols-1 gap-5 shadow-sm leading-none shadow-sm italic shadow-sm">
                    <button @click="scubaDock = !scubaDock" 
                            class="flex items-center justify-between p-7 rounded-[2.5rem] border transition-all duration-500 relative group/btn overflow-hidden shadow-2xl shadow-sm leading-none shadow-sm"
                            :class="scubaDock ? 'bg-cyan-500/10 border-cyan-500/40 ring-1 ring-cyan-500/20 shadow-sm leading-none shadow-sm' : 'bg-white/5 border-white/10 opacity-70 hover:opacity-100 shadow-sm shadow-sm italic shadow-sm '">
                        <div class="flex items-center space-x-6 relative z-10 shadow-sm leading-none shadow-sm italic shadow-sm">
                            <div class="w-16 h-16 rounded-3xl flex items-center justify-center transition-all shadow-xl border border-white/5 shadow-sm shadow-sm leading-none shadow-sm" :class="scubaDock ? 'bg-cyan-600 text-slate-950 scale-110 -rotate-3' : 'bg-slate-800 text-white/20 shadow-sm shadow-sm '">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 shadow-sm shadow-sm leading-none shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor shadow-sm leading-none shadow-sm shadow-sm italic">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <div class="text-left leading-none shadow-sm shadow-sm italic shadow-sm leading-none shadow-sm">
                                <span class="text-lg font-black block text-white italic leading-none tracking-tighter shadow-sm leading-none shadow-sm italic shadow-sm italic shadow-sm shadow-sm shadow-sm">External Scuba-Dock</span>
                                <span class="text-[10px] text-cyan-400 uppercase font-black tracking-widest block mt-3 italic tracking-tighter shadow-sm leading-none italic shadow-sm leading-none shadow-sm leading-none italic shadow-sm">Pressure-Lock Decompression active shadow-sm italic shadow-sm</span>
                            </div>
                        </div>
                    </button>
                    
                    <div class="grid grid-cols-2 gap-5 leading-none px-1 shadow-sm italic shadow-sm">
                        <div class="space-y-4 leading-none shadow-sm shadow-sm shadow-sm italic shadow-sm">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-2 italic shadow-sm shadow-sm shadow-sm">Atmosphere Core</span>
                            <button @click="atmoScrubbing = !atmoScrubbing" 
                                    class="w-full py-3.5 rounded-xl text-[10px] font-black uppercase transition-all shadow-sm shadow-sm italic shadow-sm leading-none shadow-sm italic" 
                                    :class="atmoScrubbing ? 'bg-cyan-600 text-white shadow-lg shadow-sm leading-none shadow-sm' : 'bg-white/5 text-slate-500 shadow-sm shadow-sm shadow-sm italic leading-none ' shadow-sm ">
                                Li-Hydrox Scrub
                            </button>
                        </div>
                        <div class="space-y-4 leading-none text-right shadow-sm shadow-sm shadow-sm">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pr-2 italic shadow-sm shadow-sm leading-none italic shadow-sm">Energy Core</span>
                            <button @click="hydroEnergy = !hydroEnergy" 
                                    class="w-full py-3.5 rounded-xl text-[10px] font-black uppercase transition-all shadow-sm leading-none shadow-sm" 
                                    :class="hydroEnergy ? 'bg-cyan-600 text-white shadow-lg shadow-sm leading-none shadow-sm' : 'bg-white/5 text-slate-500 shadow-sm leading-none shadow-sm' shadow-sm shadow-sm leading-none shadow-sm">
                                Tidal-Grid Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-16 p-12 bg-black rounded-[3.5rem] border border-white/10 shadow-3xl group/confirm overflow-hidden relative shadow-sm italic shadow-sm">
                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-cyan-500 to-transparent top-0 animate-[pulse_2s_infinite] shadow-sm leading-none shadow-sm"></div>
                
                <div class="flex items-center justify-between mb-10 relative z-10 leading-none shadow-sm shadow-sm italic shadow-sm shadow-sm">
                    <div>
                        <span class="text-[12px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-4 leading-none italic tracking-tighter leading-none italic shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm italic leading-none italic shadow-sm leading-none shadow-sm italic shadow-sm ">Abyssal Facility CAPEX</span>
                        <span class="text-6xl font-black text-cyan-500 italic tracking-tighter shadow-sm leading-none italic shadow-sm italic shadow-sm leading-none shadow-sm shadow-sm leading-none shadow-sm" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-10 shadow-sm italic shadow-sm">
                    <button class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-black py-7 rounded-2xl transition-all shadow-[0_30px_60px_rgba(6,182,212,0.4)] uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-[0.98] group/btnprimary relative overflow-hidden shadow-sm italic shadow-sm shadow-sm">
                        <span class="relative z-10 shadow-sm leading-none shadow-sm">Deploy Abyssal Habitat Node</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover/btnprimary:translate-x-1.5 transition-transform relative z-10 shadow-sm italic shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor shadow-sm leading-none shadow-sm shadow-sm italic">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8 shadow-sm italic " />
                        </svg>
                    </button>
                    <p class="text-center text-[9px] text-slate-500 uppercase font-black italic tracking-widest mt-2 shadow-sm leading-none italic shadow-sm">Atmospheric Equilibrium Ready (Abyssal-Core v.7.0) shadow-sm leading-none shadow-sm italic </p>
                </div>
            </div>
        </div>
    </div>
</div>
