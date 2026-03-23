@php
    declare(strict_types=1);
@endphp

<div x-data="{
    garageDepth: 12,
    liftingCapacity: 150,
    automationLevel: 'High',
    pressureSeal: true,
    smartHangar: true,
    
    get volume() {
        return this.garageDepth * 6 * 4;
    },
    
    get estimatedCost() {
        let base = this.volume * 450000;
        if (this.pressureSeal) base += 3500000;
        if (this.smartHangar) base += 1200000;
        if (this.liftingCapacity > 200) base += 2000000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[700px]">
        
        <!-- Visual: Submarine Garage Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner">
            <!-- HUD -->
            <div class="absolute top-8 left-8 z-30">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-md px-4 py-2 rounded-full border border-cyan-500/30">
                    <div class="w-2 h-2 bg-cyan-500 rounded-full animate-ping"></div>
                    <span class="text-[10px] text-cyan-400 font-black uppercase tracking-widest italic tracking-tighter">Sub-Base v.2026 Secured</span>
                </div>
            </div>

            <!-- Visualization: Deep Water Cross-Section -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(6,182,212,0.1)_0%,transparent_80%)]">
                <!-- Structural Grid -->
                <div class="absolute inset-0 opacity-10 bg-[linear-gradient(rgba(6,182,212,0.1)_1px,transparent_1px),linear-gradient(90deg,rgba(6,182,212,0.1)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
                
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-[0_0_80px_rgba(6,182,212,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Garage Vault -->
                    <path :d="`M 100 250 L 100 ${250 - garageDepth*4} Q 100 ${250 - garageDepth*4 - 40} 200 ${250 - garageDepth*4 - 40} Q 300 ${250 - garageDepth*4 - 40} 300 ${250 - garageDepth*4} L 300 250`" 
                          fill="rgba(255,255,255,0.03)" stroke="rgba(6,182,212,0.4)" stroke-width="3" stroke-dasharray="2 1" class="transition-all duration-1000" />
                    
                    <!-- Atmospheric Seal -->
                    <rect x="98" :y="245" width="204" height="10" rx="5" fill="rgba(6,182,212,0.6)" x-show="pressureSeal" />
                    
                    <!-- Submarine Generic Silhouette -->
                    <g transform="translate(140, 180) scale(1.2)">
                        <path d="M 0 20 Q 0 0 40 0 L 80 0 Q 120 0 120 20 L 120 30 Q 120 50 80 50 L 40 50 Q 0 50 0 30 Z" fill="rgba(255,255,255,0.1)" stroke="white" stroke-width="1" />
                        <rect x="50" y="-15" width="20" height="20" rx="2" fill="rgba(255,255,255,0.05)" stroke="white" stroke-width="0.5" />
                        <!-- Propeller Animation -->
                        <circle cx="120" cy="25" r="5" fill="none" stroke="cyan" stroke-width="1" class="animate-pulse" />
                    </g>

                    <!-- Lift Platforms -->
                    <g :transform="`translate(0, ${250 - garageDepth*2})`">
                        <line x1="100" y1="0" x2="300" y2="0" stroke="rgba(6,182,212,0.3)" stroke-width="4" stroke-linecap="round" />
                    </g>
                </svg>

                <!-- Water Level Indicator -->
                <div class="absolute bottom-1/4 left-0 right-0 h-[2px] bg-cyan-500/20 px-8 flex justify-between">
                    <span class="text-[8px] text-cyan-400 -mt-4 uppercase font-black italic">Mean Sea Level</span>
                    <span class="text-[8px] text-cyan-400 -mt-4 uppercase font-black italic">Pressure: 2.4 ATM</span>
                </div>
            </div>

            <!-- Telemetry Overlay -->
            <div class="p-8 grid grid-cols-2 gap-4 relative z-20">
                <div class="bg-slate-900/80 backdrop-blur-3xl p-6 rounded-[2rem] border border-cyan-500/20">
                    <span class="text-[10px] text-cyan-500 uppercase font-black block tracking-widest mb-2 leading-none">Internal Atmosphere</span>
                    <div class="flex items-end space-x-2">
                        <span class="text-3xl text-white font-black italic tracking-tighter">78%</span>
                        <span class="text-[10px] text-slate-500 font-bold uppercase pb-1 italic leading-none">Nitrogen-O2</span>
                    </div>
                </div>
                <div class="bg-slate-900/80 backdrop-blur-3xl p-6 rounded-[2rem] border border-cyan-500/20">
                    <span class="text-[10px] text-cyan-500 uppercase font-black block tracking-widest mb-2 leading-none">Structural Integrity</span>
                    <div class="flex items-end space-x-2">
                        <span class="text-3xl text-emerald-400 font-black italic tracking-tighter">100%</span>
                        <span class="text-[10px] text-slate-500 font-bold uppercase pb-1 italic leading-none">TITAN-SHIELD</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls: Base Management -->
        <div class="bg-slate-900 p-8 lg:p-12 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden group/controls">
            <!-- Scanline Effect -->
            <div class="absolute inset-x-0 h-[500px] bg-gradient-to-b from-cyan-500/5 to-transparent top-[-500px] group-hover/controls:top-full transition-all duration-[3000ms] pointer-events-none"></div>

            <div class="mb-10 relative z-10">
                <div class="inline-block px-3 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/20 mb-4">
                    <span class="text-[10px] text-cyan-400 font-black uppercase tracking-[0.2em] leading-none italic">Marine Architecture Vertical</span>
                </div>
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">Coastal Sub-Base</h3>
                <p class="text-[10px] text-slate-500 font-extrabold uppercase tracking-widest mt-4 opacity-80 leading-relaxed italic">Deep-Water Submersible Storage & Deployment Facility</p>
            </div>

            <div class="flex-grow space-y-10 relative z-10">
                <!-- Core Config -->
                <div class="space-y-6">
                    <div class="p-6 bg-white/5 rounded-3xl border border-white/5 space-y-4">
                        <div class="flex justify-between items-end mb-2 px-2">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest leading-none italic">Deployment Depth (M)</span>
                            <span class="text-2xl font-black text-cyan-400 italic leading-none tracking-tighter" x-text="garageDepth"></span>
                        </div>
                        <input type="range" x-model="garageDepth" min="8" max="30" step="1" 
                               class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                    </div>

                    <div class="p-6 bg-white/5 rounded-3xl border border-white/5 space-y-4">
                        <div class="flex justify-between items-end mb-2 px-2">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest leading-none italic">Lifting Capacity (T)</span>
                            <span class="text-2xl font-black text-white italic leading-none tracking-tighter" x-text="liftingCapacity"></span>
                        </div>
                        <input type="range" x-model="liftingCapacity" min="50" max="500" step="50" 
                               class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-white">
                    </div>
                </div>

                <!-- Specialized Modules -->
                <div class="grid grid-cols-1 gap-4">
                    <button @click="pressureSeal = !pressureSeal" 
                            class="flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 relative overflow-hidden group/btn"
                            :class="pressureSeal ? 'bg-cyan-500/10 border-cyan-500/40' : 'bg-white/5 border-white/10'">
                        <div class="relative z-10 flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors" :class="pressureSeal ? 'bg-cyan-500 text-slate-950' : 'bg-slate-800 text-white/40'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-sm font-bold block text-white italic leading-none">Hyperbaric Atmospheric Seal</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter whitespace-nowrap block mt-1 italic">Active Pressure Equalization v.4</span>
                            </div>
                        </div>
                        <div class="w-2 h-2 rounded-full relative z-10" :class="pressureSeal ? 'bg-cyan-500' : 'bg-slate-700'"></div>
                    </button>

                    <button @click="smartHangar = !smartHangar" 
                            class="flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 relative overflow-hidden"
                            :class="smartHangar ? 'bg-emerald-500/10 border-emerald-500/40' : 'bg-white/5 border-white/10'">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors" :class="smartHangar ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-white/40'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-sm font-bold block text-white italic leading-none">Smart-Hangar Automation</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter block mt-1 italic">AI-Managed Maintenance & Charge</span>
                            </div>
                        </div>
                        <div class="w-2 h-2 rounded-full" :class="smartHangar ? 'bg-emerald-500' : 'bg-slate-700'"></div>
                    </button>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-12 relative z-10 p-8 bg-black rounded-[2.5rem] border border-white/5 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-2 leading-none">Implementation Cost</span>
                        <span class="text-5xl font-black text-cyan-400 italic tracking-tighter" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-black py-5 rounded-2xl transition-all uppercase italic text-sm tracking-tighter active:scale-95 shadow-[0_15px_30px_rgba(6,182,212,0.3)]">
                        Strategic Build
                    </button>
                    <button class="bg-slate-800 hover:bg-slate-700 text-white font-black py-5 rounded-2xl transition-all uppercase italic text-sm tracking-tighter active:scale-95 border border-white/5">
                        Download BIM
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
