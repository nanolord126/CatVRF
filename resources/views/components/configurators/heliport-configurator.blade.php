@php
    declare(strict_types=1);
@endphp

<div x-data="{
    hangarWidth: 25,
    roofSolar: true,
    fuelSystem: 'Hydrogen',
    securityLevel: 'V5',
    lighting: true,
    
    get totalArea() {
        return this.hangarWidth * 20;
    },
    
    get estimatedCost() {
        let base = this.totalArea * 350000;
        if (this.roofSolar) base += 4500000;
        if (this.fuelSystem === 'Hydrogen') base += 8500000;
        if (this.securityLevel === 'V5') base += 2500000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[700px]">
        
        <!-- Visual: Heliport HUD Simulation -->
        <div class="relative bg-zinc-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5">
            <!-- HUD -->
            <div class="absolute top-8 left-8 z-30">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-md px-4 py-2 rounded-full border border-orange-500/30 shadow-lg">
                    <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] text-orange-400 font-black uppercase tracking-widest italic tracking-tighter">Sky-Base v.2026 Admin</span>
                </div>
            </div>

            <!-- Visualization: Heliport Top View -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(249,115,22,0.05)_0%,transparent_70%)]">
                <!-- Concrete Grid -->
                <div class="absolute inset-0 opacity-20 bg-[linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:50px_50px]"></div>
                
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-[0_0_100px_rgba(249,115,22,0.1)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Heliport Pad -->
                    <circle cx="200" cy="150" r="80" fill="rgba(255,255,255,0.02)" stroke="rgba(249,115,22,0.4)" stroke-width="4" stroke-dasharray="8 4" />
                    <text x="180" y="165" font-size="40" font-weight="black" fill="rgba(249,115,22,0.8)" class="italic">H</text>

                    <!-- Hangar Bay Shadow -->
                    <rect x="250" :y="50" :width="hangarWidth*4" height="200" rx="10" fill="rgba(255,255,255,0.05)" stroke="white" stroke-opacity="0.2" class="transition-all duration-700" />
                    
                    <!-- Solar Panels Pattern -->
                    <g x-show="roofSolar" class="transition-opacity">
                        <template x-for="i in 10">
                             <line :x1="255 + (i*8)" y1="55" :x2="255 + (i*8)" y2="245" stroke="rgba(249,115,22,0.3)" stroke-width="0.5" />
                        </template>
                    </g>

                    <!-- Landing Lights -->
                    <g x-show="lighting">
                        <template x-for="a in [0, 90, 180, 270]">
                            <circle :cx="200 + 100 * Math.cos(a * Math.PI / 180)" :cy="150 + 100 * Math.sin(a * Math.PI / 180)" r="3" fill="#f97316" class="animate-pulse" />
                        </template>
                    </g>
                </svg>

                <!-- Weather/Aero Telemetry -->
                <div class="absolute top-1/4 right-8 space-y-4">
                    <div class="bg-black/60 p-4 rounded-2xl border border-white/5 backdrop-blur-xl">
                        <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest leading-none">Wind Velocity</span>
                        <span class="text-xl text-white font-black italic">12 kts</span>
                    </div>
                    <div class="bg-black/60 p-4 rounded-2xl border border-white/5 backdrop-blur-xl">
                        <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest leading-none">Safe Ceiling</span>
                        <span class="text-xl text-white font-black italic tracking-tighter">600m</span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="p-8 grid grid-cols-2 gap-4 relative z-20">
                <div class="bg-black/40 backdrop-blur-3xl p-6 rounded-[2rem] border border-orange-500/10">
                    <span class="text-[9px] text-orange-500 uppercase font-black block tracking-widest mb-2 leading-none">Fuel Reserves (H)</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="fuelSystem === 'Hydrogen' ? '12,000 L' : '4,500 L'"></span>
                </div>
                <div class="bg-black/40 backdrop-blur-3xl p-6 rounded-[2rem] border border-orange-500/10 text-right">
                    <span class="text-[9px] text-orange-500 uppercase font-black block tracking-widest mb-2 leading-none">Security Grade</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="securityLevel"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Aero Infrastructure -->
        <div class="bg-zinc-900 p-8 lg:p-12 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden group/controls">
            <!-- Scanline -->
            <div class="absolute inset-x-0 h-[500px] bg-gradient-to-b from-orange-500/5 to-transparent top-[-500px] group-hover/controls:top-full transition-all duration-[4000ms] pointer-events-none"></div>

            <div class="mb-10 relative z-10">
                <div class="inline-block px-3 py-1 rounded-lg bg-orange-500/10 border border-orange-500/20 mb-4">
                    <span class="text-[10px] text-orange-400 font-black uppercase tracking-[0.2em] leading-none italic tracking-tighter">Coastal Aero-Vertical</span>
                </div>
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">Maritime Heliport</h3>
                <p class="text-[10px] text-slate-500 font-extrabold uppercase tracking-widest mt-4 opacity-80 leading-relaxed italic tracking-tighter leading-none">Reinforced Aero-Base with Integrated Hangar & H2 Fueling</p>
            </div>

            <div class="flex-grow space-y-10 relative z-10">
                <!-- Dimension Slider -->
                <div class="p-6 bg-white/5 rounded-3xl border border-white/5 space-y-4">
                    <div class="flex justify-between items-end mb-2 px-2">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none">Hangar Bay Width (M)</span>
                        <span class="text-2xl font-black text-orange-400 italic tracking-tighter leading-none" x-text="hangarWidth"></span>
                    </div>
                    <input type="range" x-model="hangarWidth" min="15" max="40" step="5" 
                           class="w-full h-2 bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-orange-500">
                </div>

                <!-- Logistics & Energy -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none pl-2">Fuel Type</span>
                        <select x-model="fuelSystem" class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-4 text-white text-sm font-bold italic outline-none focus:border-orange-500/50 transition-all appearance-none">
                            <option value="JetA1">Jet A-1 Aviation</option>
                            <option value="Hydrogen">Hydrogen Zero-E</option>
                        </select>
                    </div>
                    <div class="space-y-4">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none pl-2">Security-OS</span>
                        <select x-model="securityLevel" class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-4 text-white text-sm font-bold italic outline-none focus:border-orange-500/50 transition-all appearance-none">
                            <option value="Standard">Standard Secured</option>
                            <option value="V5">V5 Mil-Grade</option>
                        </select>
                    </div>
                </div>

                <!-- Tech Toggles -->
                <div class="space-y-3">
                    <button @click="roofSolar = !roofSolar" 
                            class="w-full flex items-center justify-between p-6 rounded-3xl border transition-all duration-300 group/btn"
                            :class="roofSolar ? 'bg-orange-500/10 border-orange-500/40 shadow-[0_10px_30px_rgba(249,115,22,0.1)]' : 'bg-white/5 border-white/10'">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors" :class="roofSolar ? 'bg-orange-500 text-slate-950 shadow-lg' : 'bg-zinc-800 text-white/40'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-sm font-bold block text-white italic leading-none">Photovoltaic Hangar Roof</span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-tighter block mt-1 italic tracking-tighter">Energy Autonomy Module active</span>
                            </div>
                        </div>
                        <div class="w-2 h-2 rounded-full shadow-inner" :class="roofSolar ? 'bg-orange-500 ring-4 ring-orange-500/20' : 'bg-zinc-700'"></div>
                    </button>
                </div>
            </div>

            <!-- Finalization -->
            <div class="mt-12 p-8 bg-black rounded-[2.5rem] border border-white/5 relative overflow-hidden">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-2 leading-none italic tracking-tighter">Total Capex Estimate</span>
                        <span class="text-5xl font-black text-white italic tracking-tighter" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <button class="w-full bg-white hover:bg-slate-200 text-slate-950 font-black py-6 rounded-2xl transition-all shadow-[0_20px_40px_rgba(255,255,255,0.1)] uppercase italic text-sm tracking-tighter flex items-center justify-center space-x-3 group/confirm">
                        <span>Initiate Project Certification</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/confirm:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                    <button class="text-white/40 hover:text-white text-[9px] font-black uppercase tracking-widest transition-colors py-2 italic tracking-tighter">
                        Request Site Survey & Noise Impact Study
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
