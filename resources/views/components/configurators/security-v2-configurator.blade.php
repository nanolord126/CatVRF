@php
    declare(strict_types=1);
@endphp

<div x-data="{
    defenseLayers: 3,
    cyberShield: true,
    empHardening: true,
    stealthMode: false,
    aiTactical: 'Advanced',
    
    get powerConsumption() {
        return this.defenseLayers * 15 + (this.cyberShield ? 20 : 0) + (this.aiTactical === 'Advanced' ? 30 : 10);
    },
    
    get estimatedCost() {
        let base = this.defenseLayers * 4500000;
        if (this.cyberShield) base += 2500000;
        if (this.empHardening) base += 3500000;
        if (this.aiTactical === 'Advanced') base += 5500000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group/main">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px]">
        
        <!-- Visual: Security Grid Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz">
            <!-- HUD -->
            <div class="absolute top-8 left-8 z-40">
                <div class="flex items-center space-x-3 bg-black/80 backdrop-blur-xl px-5 py-2.5 rounded-full border border-red-500/40 shadow-[0_0_30px_rgba(239,68,68,0.2)]">
                    <div class="w-2.5 h-2.5 bg-red-600 rounded-full animate-ping"></div>
                    <span class="text-[10px] text-red-500 font-black uppercase tracking-[0.3em] italic tracking-tighter shadow-sm leading-none">Aegis-OS v.2026.4 Tactical</span>
                </div>
            </div>

            <!-- Visualization: Perimeter Defense Map -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(239,68,68,0.1)_0%,transparent_80%)]">
                <!-- Military Grid -->
                <div class="absolute inset-0 opacity-20 bg-[linear-gradient(rgba(239,68,68,0.1)_1px,transparent_1px),linear-gradient(90deg,rgba(239,68,68,0.1)_1px,transparent_1px)] bg-[size:30px_30px]"></div>
                
                <svg viewBox="0 0 400 400" class="w-full h-full drop-shadow-[0_0_100px_rgba(239,68,68,0.15)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Base Perimeter -->
                    <circle cx="200" cy="200" r="40" fill="rgba(255,255,255,0.05)" stroke="white" stroke-width="2" class="animate-pulse" />
                    
                    <!-- Defense Rings -->
                    <template x-for="i in parseInt(defenseLayers)">
                        <circle cx="200" cy="200" :r="40 + i*40" fill="none" :stroke="`rgba(239,68,68, ${0.1 + i*0.1})`" stroke-width="1.5" stroke-dasharray="10 5" class="transition-all duration-1000" />
                    </template>

                    <!-- Cyber Shield Visual -->
                    <g x-show="cyberShield" class="transition-opacity">
                        <circle cx="200" cy="200" r="180" fill="none" stroke="#ef4444" stroke-width="1" stroke-dasharray="2 2" class="animate-[spin_60s_linear_infinite]" />
                        <circle cx="200" cy="200" r="175" fill="none" stroke="#ef4444" stroke-width="0.5" x-show="empHardening" />
                    </g>

                    <!-- Scanning Line -->
                    <line x1="200" y1="200" x2="200" y2="20" stroke="#ef4444" stroke-width="1" class="animate-[spin_4s_linear_infinite] origin-center opacity-40" />

                    <!-- Tactical Overlays -->
                    <g x-show="aiTactical === 'Advanced'">
                        <rect x="300" y="50" width="60" height="20" rx="4" fill="rgba(239,68,68,0.2)" stroke="#ef4444" stroke-width="1" />
                        <text x="305" y="64" fill="#ef4444" font-size="8" font-weight="black" class="italic tracking-tighter italic">AI-TARGETING</text>
                    </g>
                </svg>

                <!-- Status Markers -->
                <div class="absolute bottom-12 left-12 space-y-2">
                    <div class="flex items-center space-x-2 bg-black/60 px-3 py-1.5 rounded-lg border border-white/5">
                        <div class="w-1.5 h-1.5 bg-red-500 rounded-full shadow-[0_0_5px_#ef4444]"></div>
                        <span class="text-[8px] text-white font-black uppercase italic tracking-tighter">Perimeter Leak: 0%</span>
                    </div>
                </div>
            </div>

            <!-- Telemetry HUD -->
            <div class="p-10 grid grid-cols-2 gap-5 relative z-20">
                <div class="bg-slate-900/90 backdrop-blur-3xl p-6 rounded-[2rem] border border-red-500/20 group-hover/viz:border-red-500/40 transition-colors">
                    <div class="flex justify-between items-start mb-2 leading-none">
                        <span class="text-[10px] text-red-500 uppercase font-black block tracking-widest leading-none tracking-tighter">Shield Load</span>
                        <span class="text-[8px] text-slate-500 font-bold italic leading-none tracking-tighter leading-none">NOMINAL</span>
                    </div>
                    <div class="h-2 w-full bg-white/5 rounded-full overflow-hidden mt-3">
                        <div class="h-full bg-red-600 transition-all duration-1000" :style="`width: ${powerConsumption}%`"></div>
                    </div>
                </div>
                <div class="bg-slate-900/90 backdrop-blur-3xl p-6 rounded-[2rem] border border-red-500/20 group-hover/viz:border-red-500/40 transition-colors">
                    <span class="text-[10px] text-red-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Tactical AI</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="aiTactical"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Tactical Deployment -->
        <div class="bg-slate-900 p-8 lg:p-14 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden group/controls">
            <!-- Tactical Pulse -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(239,68,68,0.03)_0%,transparent_60%)] group-hover/controls:animate-pulse pointer-events-none"></div>

            <div class="mb-12 relative z-10">
                <div class="inline-block px-3 py-1 rounded-lg bg-red-500/10 border border-red-500/20 mb-5">
                    <span class="text-[10px] text-red-500 font-black uppercase tracking-[0.2em] leading-none italic tracking-tighter leading-none shadow-sm">Defense Management</span>
                </div>
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none italic shadow-sm">Estate Aegis-V2</h3>
                <p class="text-[11px] text-slate-500 font-extrabold uppercase tracking-widest mt-5 opacity-80 leading-relaxed italic tracking-tighter leading-none">Military-Grade Perimeter Security & Cyber-Hardened Infrastructure Design</p>
            </div>

            <div class="flex-grow space-y-10 relative z-10 px-2">
                <!-- Perimeter Density -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1">Hardened Defense Layers</span>
                        <span class="text-2xl font-black text-red-500 italic tracking-tighter leading-none" x-text="defenseLayers"></span>
                    </div>
                    <div class="relative py-2">
                        <input type="range" x-model="defenseLayers" min="1" max="4" step="1" 
                               class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-red-600 shadow-inner">
                        <div class="absolute top-8 left-0 w-full flex justify-between px-1">
                            <template x-for="p in 4">
                                <div class="w-1 h-3 bg-white/5 rounded-full"></div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Strategic Modules -->
                <div class="grid grid-cols-1 gap-4">
                    <button @click="cyberShield = !cyberShield" 
                            class="flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 relative group/btn overflow-hidden"
                            :class="cyberShield ? 'bg-red-500/10 border-red-500/40 shadow-[0_15px_40px_rgba(239,68,68,0.1)]' : 'bg-white/5 border-white/10'">
                        <div class="flex items-center space-x-5 relative z-10">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all shadow-lg" :class="cyberShield ? 'bg-red-600 text-slate-950 scale-105 rotate-3' : 'bg-slate-800 text-white/30'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-base font-black block text-white italic leading-none tracking-tighter italic leading-none">Network Intrusion Hyper-Shield</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter block mt-2 italic tracking-tighter leading-none italic shadow-sm">Neural-Pattern Recognition Active</span>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full relative z-10 shadow-inner" :class="cyberShield ? 'bg-red-500 ring-4 ring-red-500/20' : 'bg-slate-700'"></div>
                    </button>

                    <button @click="empHardening = !empHardening" 
                            class="flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 relative group/btn overflow-hidden"
                            :class="empHardening ? 'bg-zinc-800 border-zinc-500/40' : 'bg-white/5 border-white/10'">
                        <div class="flex items-center space-x-5">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all bg-zinc-800 text-white shadow-xl border border-white/5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-base font-black block text-white italic leading-none tracking-tighter italic leading-none">EMP / Solar Flare Shielding</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter block mt-2 italic tracking-tighter leading-none italic shadow-sm">Faraday Cage v2 Integrated</span>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full shadow-inner" :class="empHardening ? 'bg-white' : 'bg-slate-700'"></div>
                    </button>
                </div>
            </div>

            <!-- Deployment Confirmation -->
            <div class="mt-14 p-10 bg-black rounded-[3rem] border border-white/5 shadow-3xl group/confirm overflow-hidden relative">
                <!-- Ambient Glow -->
                <div class="absolute inset-0 bg-red-500/5 translate-y-full group-hover/confirm:translate-y-0 transition-transform duration-700"></div>
                
                <div class="flex items-center justify-between mb-8 relative z-10">
                    <div>
                        <span class="text-[11px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-3 leading-none italic tracking-tighter italic leading-none italic shadow-sm">Defense Capex Estimate</span>
                        <span class="text-5xl font-black text-red-600 italic tracking-tighter italic shadow-sm" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-10">
                    <button class="w-full bg-red-600 hover:bg-red-500 text-slate-950 font-black py-6 rounded-2xl transition-all shadow-[0_25px_50px_rgba(220,38,38,0.4)] uppercase italic text-sm tracking-widest flex items-center justify-center space-x-4 active:scale-95 active:shadow-none group/btnconfirm">
                        <span class="relative z-10">Initiate Full Core Deployment</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform group-hover/btnconfirm:translate-x-1 transition-transform relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                    <div class="flex justify-center">
                        <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest italic tracking-tighter italic leading-none italic shadow-sm">Awaiting Strategic Protocol Signature (v.2026.4)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
