@php
    declare(strict_types=1);
@endphp

<div x-data="{
    antennaArray: 4,
    uplinkStrength: 25,
    lbandActive: true,
    kubandActive: true,
    kaBandActive: false,
    deepSpaceMode: false,
    
    get throughputGbs() {
        let base = (this.antennaArray * 1.25);
        if (this.kaBandActive) base *= 2.5;
        if (this.deepSpaceMode) base *= 0.5; // Focus on range, not speed
        return base.toFixed(2);
    },
    
    get powerDrawMw() {
        let base = this.antennaArray * 0.8;
        if (this.kaBandActive) base += 2.5;
        if (this.deepSpaceMode) base += 5.0;
        return base.toFixed(1);
    },

    get estimatedCost() {
        let base = this.antennaArray * 4500000;
        if (this.kaBandActive) base += 12500000;
        if (this.deepSpaceMode) base += 35000000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px]">
        
        <!-- Visual: Satellite Dish Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl">
            <!-- HUD -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/80 backdrop-blur-2xl px-5 py-2.5 rounded-full border border-sky-500/40 shadow-[0_0_40px_rgba(14,165,233,0.2)]">
                    <div class="w-2.5 h-2.5 bg-sky-500 rounded-full animate-[ping_2s_linear_infinite]"></div>
                    <span class="text-[10px] text-sky-400 font-black uppercase tracking-[0.3em] italic tracking-tighter leading-none">Space-Link v.2.0 Uplink Active</span>
                </div>
            </div>

            <!-- Visualization: Ground Station Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(14,165,233,0.15)_0%,transparent_80%)]">
                <!-- Orbital Paths -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-1/2 left-1/2 w-[600px] h-[600px] border border-white rounded-full -translate-x-1/2 -translate-y-1/2 animate-[spin_60s_linear_infinite]"></div>
                    <div class="absolute top-1/2 left-1/2 w-[400px] h-40 border border-white rounded-full -translate-x-1/2 -translate-y-1/2 rotate-45 animate-[spin_40s_linear_infinite_reverse]"></div>
                </div>

                <svg viewBox="0 0 400 400" class="w-full h-full drop-shadow-[0_0_120px_rgba(14,165,233,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Ground Dish -->
                    <path :d="deepSpaceMode ? 'M 100 250 Q 200 150 300 250 L 250 350 L 150 350 Z' : 'M 120 280 Q 200 200 280 280 L 240 350 L 160 350 Z'" 
                          fill="rgba(255,255,255,0.05)" stroke="#0ea5e9" stroke-width="2" class="transition-all duration-1000" />
                    
                    <!-- Signal Beam -->
                    <template x-if="uplinkStrength > 0">
                        <line x1="200" y1="200" x2="200" y2="50" stroke="#0ea5e9" :stroke-width="uplinkStrength / 5" stroke-dasharray="10,5" class="animate-[pulse_1s_infinite] transition-all duration-500 opacity-60" />
                    </template>

                    <!-- Array Layout -->
                    <template x-for="i in parseInt(antennaArray/2)">
                        <circle :cx="120 + i*80" cy="360" r="10" fill="rgba(14,165,233,0.2)" stroke="#0ea5e9" stroke-width="1" />
                    </template>
                </svg>

                <!-- Signal Telemetry -->
                <div class="absolute bottom-16 inset-x-12 flex justify-between">
                    <div class="space-y-2">
                        <div class="text-[8px] text-sky-400 font-black uppercase tracking-widest pl-1 leading-none italic italic">Azimuth</div>
                        <div class="h-1 w-24 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-sky-500 animate-[pulse_2s_infinite]" style="width: 65%"></div>
                        </div>
                    </div>
                    <div class="space-y-2 text-right">
                        <div class="text-[8px] text-sky-400 font-black uppercase tracking-widest leading-none italic italic">Elevation</div>
                        <div class="h-1 w-24 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-sky-600 animate-[pulse_3s_infinite]" style="width: 42%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perf Stats Overlay -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-20">
                <div class="bg-sky-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-sky-500/20">
                    <span class="text-[9px] text-sky-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic">Throughput</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter italic shadow-sm" x-text="throughputGbs + ' Gb/s'"></span>
                </div>
                <div class="bg-sky-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-sky-500/20 text-center">
                    <span class="text-[9px] text-sky-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic italic">Power Load</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter italic shadow-sm" x-text="powerDrawMw + ' MW'"></span>
                </div>
                <div class="bg-sky-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-sky-500/20 text-right">
                    <span class="text-[9px] text-sky-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic shadow-sm leading-none italic italic">Latency</span>
                    <span class="text-2xl text-emerald-400 font-black italic tracking-tighter italic shadow-sm leading-none" x-text="deepSpaceMode ? '2.4s' : '0.05s'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Configuration -->
        <div class="bg-white/[0.02] p-8 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden group/controls">
            <!-- Space Particles -->
            <div class="absolute inset-0 opacity-[0.05] pointer-events-none overflow-hidden">
                <template x-for="i in 50">
                    <div class="absolute w-1 h-1 bg-white rounded-full animate-pulse" 
                         :style="`top: ${Math.random()*100}%; left: ${Math.random()*100}%; animation-delay: ${Math.random()*5}s` text-indigo-500 h-1"></div>
                </template>
            </div>

            <div class="mb-14 relative z-10">
                <div class="inline-block px-4 py-1.5 rounded-xl bg-sky-500/10 border border-sky-500/20 mb-6 shadow-xl leading-none italic tracking-tighter leading-none shadow-sm">
                    <span class="text-[10px] text-sky-400 font-black uppercase tracking-[0.3em] leading-none italic shadow-sm leading-none italic">Aero-Space Vertical</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none italic tracking-tighter shadow-sm leading-none">Star-Link Ground Station</h3>
                <p class="text-[12px] text-slate-500 font-extrabold uppercase tracking-widest mt-6 opacity-80 leading-relaxed italic tracking-tighter leading-none shadow-sm italic shadow-sm leading-none italic">LEO/MEO Satellite Relay Node with Cryptographic Multi-Band
                Carrier-Grade Telemetry v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-10 px-4">
                <!-- Antenna Count Slider -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic">Antenna Array Nodes</span>
                        <span class="text-3xl font-black text-sky-500 italic tracking-tighter italic shadow-sm" x-text="antennaArray"></span>
                    </div>
                    <div class="relative py-3 leading-none">
                        <input type="range" x-model="antennaArray" min="2" max="16" step="2" 
                               class="w-full h-2.5 bg-slate-900 rounded-full appearance-none cursor-pointer accent-sky-500 shadow-inner">
                    </div>
                </div>

                <!-- Range Toggle -->
                <div class="grid grid-cols-1 gap-5">
                    <button @click="deepSpaceMode = !deepSpaceMode" 
                            class="flex items-center justify-between p-7 rounded-[2.5rem] border transition-all duration-500 relative group/btn overflow-hidden shadow-2xl"
                            :class="deepSpaceMode ? 'bg-sky-500/10 border-sky-500/40' : 'bg-white/5 border-white/10 opacity-70'">
                        <div class="flex items-center space-x-6 relative z-10 leading-none shadow-sm">
                            <div class="w-16 h-16 rounded-3xl flex items-center justify-center transition-all shadow-xl bg-slate-800" :class="deepSpaceMode ? 'bg-sky-600 text-slate-950 scale-110' : 'text-white/20'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>
                            <div class="text-left leading-none shadow-sm">
                                <span class="text-lg font-black block text-white italic leading-none tracking-tighter leading-none shadow-sm italic shadow-sm">Deep Space Core Mode</span>
                                <span class="text-[10px] text-sky-400 uppercase font-black tracking-widest block mt-3 italic tracking-tighter leading-none shadow-sm italic leading-none shadow-sm">Inter-Planetary Signal Hardening active</span>
                            </div>
                        </div>
                    </button>
                    
                    <!-- Band Selectors -->
                    <div class="grid grid-cols-3 gap-4">
                        <button @click="kaBandActive = !kaBandActive" 
                                class="py-4 rounded-2xl border transition-all text-[9px] font-black uppercase tracking-widest leading-none shadow-sm"
                                :class="kaBandActive ? 'bg-sky-600 text-white border-sky-400' : 'bg-white/5 text-slate-500 border-white/10'">
                            Ka-Band
                        </button>
                        <button class="py-4 rounded-2xl border bg-sky-600/20 text-sky-400 border-sky-500/40 text-[9px] font-black uppercase tracking-widest opacity-50 cursor-not-allowed leading-none shadow-sm">Ku-Band</button>
                        <button class="py-4 rounded-2xl border bg-sky-600/20 text-sky-400 border-sky-500/40 text-[9px] font-black uppercase tracking-widest opacity-50 cursor-not-allowed leading-none shadow-sm">L-Band</button>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-16 p-12 bg-black rounded-[3.5rem] border border-white/10 shadow-3xl group/confirm overflow-hidden relative">
                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-sky-500 to-transparent top-0 animate-[pulse_2s_infinite]"></div>
                
                <div class="flex items-center justify-between mb-10 relative z-10 leading-none shadow-sm">
                    <div>
                        <span class="text-[12px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-4 leading-none italic tracking-tighter leading-none italic shadow-sm leading-none shadow-sm italic">Ground Segment CAPEX</span>
                        <span class="text-6xl font-black text-sky-500 italic tracking-tighter italic shadow-sm leading-none" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-10">
                    <button class="w-full bg-sky-600 hover:bg-sky-500 text-white font-black py-7 rounded-2xl transition-all shadow-[0_30px_60px_rgba(14,165,233,0.4)] uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-[0.98] group/btnprimary relative overflow-hidden">
                        <span class="relative z-10">Deploy Uplink Node</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover/btnprimary:translate-x-1.5 transition-transform relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                    <p class="text-center text-[9px] text-slate-500 uppercase font-black italic tracking-widest mt-2">Space-Sync Protocol Handshake Ready (Relay v.2.0)</p>
                </div>
            </div>
        </div>
    </div>
</div>
