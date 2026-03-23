@php
    declare(strict_types=1);
@endphp

<div x-data="{
    cargoVolume: 500,
    tempControl: true,
    automation: 'Full',
    dronePads: 4,
    securityGrade: 'Military',
    
    get throughput() {
        return Math.round(this.cargoVolume * (this.automation === 'Full' ? 1.8 : 1.2));
    },
    
    get estimatedCost() {
        let base = this.cargoVolume * 150000;
        if (this.tempControl) base += 4500000;
        if (this.automation === 'Full') base += 12500000;
        base += this.dronePads * 1200000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px]">
        
        <!-- Visual: Logistics Hub HUD -->
        <div class="relative bg-zinc-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner">
            <!-- HUD -->
            <div class="absolute top-8 left-8 z-30">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-md px-4 py-2 rounded-full border border-yellow-500/30">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-ping"></div>
                    <span class="text-[10px] text-yellow-500 font-black uppercase tracking-widest italic tracking-tighter shadow-sm">Logi-Core v.2026 Grid</span>
                </div>
            </div>

            <!-- Visualization: Warehouse Grid -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(234,179,8,0.05)_0%,transparent_70%)]">
                <div class="absolute inset-0 opacity-10 bg-[linear-gradient(rgba(234,179,8,0.1)_1px,transparent_1px),linear-gradient(90deg,rgba(234,179,8,0.1)_1px,transparent_1px)] bg-[size:30px_30px]"></div>
                
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-[0_0_80px_rgba(234,179,8,0.1)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Warehouse Structure -->
                    <path :d="`M 50 250 L 50 100 L 350 100 L 350 250 Z`" fill="rgba(255,255,255,0.02)" stroke="rgba(234,179,8,0.3)" stroke-width="2" />
                    
                    <!-- Dynamic Shelving Units -->
                    <template x-for="i in 6">
                        <rect :x="70 + (i*45)" :y="120" width="30" :height="110" rx="2" 
                              :fill="automation === 'Full' ? 'rgba(234,179,8,0.1)' : 'rgba(255,255,255,0.05)'" 
                              class="transition-all duration-700" />
                    </template>

                    <!-- Drone Pads Visual -->
                    <template x-for="p in parseInt(dronePads)">
                        <g :transform="`translate(${60 + p*60}, 85)`">
                            <circle r="12" fill="none" stroke="#eab308" stroke-width="1" stroke-dasharray="2 2" />
                            <text x="-4" y="4" fill="#eab308" font-size="10" font-weight="black">D</text>
                            <circle cx="0" cy="0" r="2" fill="#eab308" class="animate-pulse" />
                        </g>
                    </template>

                    <!-- Temp Control Shield -->
                    <rect x="45" y="95" width="310" height="160" rx="12" fill="none" stroke="#ef4444" stroke-width="1" stroke-dasharray="4 4" x-show="tempControl" class="opacity-40" />
                </svg>
            </div>

            <!-- Dashboard Analytics -->
            <div class="p-8 grid grid-cols-2 gap-4 relative z-20">
                <div class="bg-black/40 backdrop-blur-3xl p-6 rounded-[2rem] border border-white/5">
                    <span class="text-[9px] text-yellow-500 uppercase font-black block tracking-widest mb-2 leading-none italic tracking-tighter leading-none">Flow Throughput</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter" x-text="throughput + ' units/h'"></span>
                </div>
                <div class="bg-black/40 backdrop-blur-3xl p-6 rounded-[2rem] border border-white/5">
                    <span class="text-[9px] text-yellow-500 uppercase font-black block tracking-widest mb-2 leading-none italic tracking-tighter leading-none">Security Status</span>
                    <span class="text-3xl text-emerald-400 font-black italic tracking-tighter" x-text="securityGrade"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Hub Specification -->
        <div class="bg-zinc-900 p-8 lg:p-14 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden group/controls">
            <div class="mb-10 relative z-10">
                <div class="inline-block px-3 py-1 rounded-lg bg-yellow-500/10 border border-yellow-500/20 mb-5">
                    <span class="text-[10px] text-yellow-500 font-black uppercase tracking-[0.2em] leading-none italic tracking-tighter leading-none">Vertical: Logistics & Supply</span>
                </div>
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none italic shadow-sm">Smart Logistic Hub</h3>
                <p class="text-[11px] text-slate-500 font-extrabold uppercase tracking-widest mt-5 opacity-80 leading-relaxed italic tracking-tighter leading-none whitespace-pre-line">Autonomous Fulfillment Center with Drone Integration
                & Multi-Zone Climate Management</p>
            </div>

            <div class="flex-grow space-y-10 relative z-10">
                <!-- Capacity Slider -->
                <div class="p-6 bg-white/5 rounded-3xl border border-white/5 space-y-4">
                    <div class="flex justify-between items-end mb-2 px-2 leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic">Storage Volume (m³)</span>
                        <span class="text-2xl font-black text-yellow-500 italic tracking-tighter leading-none" x-text="cargoVolume"></span>
                    </div>
                    <input type="range" x-model="cargoVolume" min="100" max="2500" step="100" 
                           class="w-full h-2 bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-yellow-500 shadow-inner">
                </div>

                <!-- Drone Network -->
                <div class="p-6 bg-white/5 rounded-3xl border border-white/5 space-y-4">
                    <div class="flex justify-between items-end mb-2 px-2 leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic">Drone Landing Vertiports</span>
                        <span class="text-2xl font-black text-white italic tracking-tighter leading-none" x-text="dronePads"></span>
                    </div>
                    <input type="range" x-model="dronePads" min="0" max="12" step="1" 
                           class="w-full h-2 bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-white shadow-inner">
                </div>

                <!-- Strategic Options -->
                <div class="grid grid-cols-1 gap-4">
                    <button @click="tempControl = !tempControl" 
                            class="flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 relative group/btn overflow-hidden"
                            :class="tempControl ? 'bg-yellow-500/10 border-yellow-500/40 shadow-[0_15px_40px_rgba(234,179,8,0.1)]' : 'bg-white/5 border-white/10'">
                        <div class="flex items-center space-x-5 relative z-10 leading-none">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all shadow-lg" :class="tempControl ? 'bg-yellow-500 text-slate-950 scale-105 rotate-3' : 'bg-zinc-800 text-white/30'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <div class="text-left leading-none">
                                <span class="text-base font-black block text-white italic leading-none tracking-tighter italic leading-none">Pharma-Grade Temp Control</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter block mt-2 italic tracking-tighter leading-none italic shadow-sm">-20°C to +8°C Active Regulation</span>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full relative z-10 shadow-inner" :class="tempControl ? 'bg-yellow-500 ring-4 ring-yellow-500/20' : 'bg-zinc-700'"></div>
                    </button>
                    
                    <div class="grid grid-cols-2 gap-4 leading-none">
                        <select x-model="automation" class="bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-white text-sm font-black italic tracking-tighter outline-none focus:border-yellow-500/50 transition-all appearance-none leading-none shadow-sm">
                            <option value="Standard">Partial Automation</option>
                            <option value="Full">Full AI Fulfillment</option>
                        </select>
                        <select x-model="securityGrade" class="bg-white/5 border border-white/10 rounded-2xl py-5 px-6 text-white text-sm font-black italic tracking-tighter outline-none focus:border-yellow-500/50 transition-all appearance-none leading-none shadow-sm">
                            <option value="Standard">Standard Secure</option>
                            <option value="Military">Military Grade</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-12 p-10 bg-black rounded-[3rem] border border-white/5 shadow-3xl group/confirm overflow-hidden relative">
                <div class="flex items-center justify-between mb-8 relative z-10 leading-none">
                    <div>
                        <span class="text-[11px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-3 leading-none italic tracking-tighter leading-none italic shadow-sm italic shadow-sm">Industrial Hub Capex</span>
                        <span class="text-5xl font-black text-yellow-500 italic tracking-tighter italic shadow-sm" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <button class="w-full bg-yellow-500 hover:bg-yellow-400 text-slate-950 font-black py-6 rounded-2xl transition-all shadow-[0_25px_50px_rgba(234,179,8,0.3)] uppercase italic text-sm tracking-widest flex items-center justify-center space-x-4 active:scale-95 group/btnconfirm relative z-10">
                    <span>Export Infrastructure Schematic</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform group-hover/btnconfirm:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
