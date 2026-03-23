<div x-data="{
    height: 450,
    panels: 8,
    branchType: 'organic', // geometric, organic, minimalist
    output: 4.5,
    batteryCap: 20,
    nightLighting: true,
    usbCharging: true,
    correlationId: '{{ Str::uuid() }}',
    
    get totalKw() { return (this.panels * 0.45).toFixed(1); },
    get price() { 
        let base = this.panels * 12000;
        base += this.height * 200;
        if (this.branchType === 'organic') base *= 1.4;
        if (this.batteryCap > 0) base += (this.batteryCap * 1500);
        return Math.round(base);
    }
}" class="p-6 bg-[#0a0f0d] border border-sky-500/20 rounded-3xl text-white shadow-2xl relative overflow-hidden">
    
    <!-- Neon Background Glow -->
    <div class="absolute -top-24 -left-24 w-64 h-64 bg-sky-500/10 rounded-full blur-[100px]"></div>
    
    <div class="flex justify-between items-center mb-8 relative z-10">
        <div>
            <h3 class="text-3xl font-black italic tracking-tighter text-sky-400">SOLAR TREE <span class="text-white/20">NEO-GEN</span></h3>
            <p class="text-[10px] font-mono text-sky-500/50 uppercase tracking-[0.3em]">Autonomous Power Infrastructure</p>
        </div>
        <div class="bg-sky-500/10 px-4 py-2 rounded-lg border border-sky-500/20 text-right">
            <span class="block text-[10px] text-sky-500/40 uppercase font-mono">Est. Investment</span>
            <span class="text-2xl font-bold text-sky-400" x-text="price.toLocaleString() + ' ₽'"></span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- SVG Tree Simulation -->
        <div class="relative bg-black/40 rounded-2xl border border-sky-500/5 aspect-[4/5] flex items-center justify-center overflow-hidden">
            <svg viewBox="0 0 500 700" class="w-full h-full">
                <!-- Grid Lines -->
                <g stroke="rgba(0,191,255,0.05)" stroke-width="0.5">
                    <template x-for="i in 10">
                        <line :x1="i*50" y1="0" :x2="i*50" y2="700" />
                    </template>
                </g>

                <!-- Ground -->
                <path d="M 50 650 Q 250 630 450 650" fill="none" stroke="rgba(0,191,255,0.2)" stroke-width="2" />
                
                <!-- Main Trunk -->
                <path d="M 250 650 L 250 400" stroke="#0ea5e9" stroke-width="12" stroke-linecap="round" />
                <path d="M 250 650 L 250 400" stroke="white" stroke-width="2" stroke-opacity="0.2" />

                <!-- organic branches -->
                <g x-show="branchType === 'organic'">
                    <path d="M 250 450 C 150 400 120 300 100 250" fill="none" stroke="#0ea5e9" stroke-width="4" stroke-linecap="round">
                        <animate attributeName="stroke-dasharray" from="0,1000" to="1000,0" dur="2s" fill="freeze" />
                    </path>
                    <path d="M 250 430 C 350 380 380 280 400 230" fill="none" stroke="#0ea5e9" stroke-width="4" stroke-linecap="round" />
                    <path d="M 250 400 C 200 300 200 200 250 150" fill="none" stroke="#0ea5e9" stroke-width="4" stroke-linecap="round" />
                    
                    <!-- Panel Heads -->
                    <circle cx="100" cy="250" r="15" fill="#1e293b" stroke="#0ea5e9" stroke-width="2" />
                    <circle cx="400" cy="230" r="15" fill="#1e293b" stroke="#0ea5e9" stroke-width="2" />
                    <circle cx="250" cy="150" r="15" fill="#1e293b" stroke="#0ea5e9" stroke-width="2" />
                </g>

                <!-- geometric branches -->
                <g x-show="branchType === 'geometric'">
                    <line x1="250" y1="420" x2="100" y2="300" stroke="#0ea5e9" stroke-width="6" />
                    <line x1="250" y1="420" x2="400" y2="300" stroke="#0ea5e9" stroke-width="6" />
                    <rect x="75" y="275" width="50" height="30" rx="4" fill="#1e293b" stroke="#0ea5e9" />
                    <rect x="375" y="275" width="50" height="30" rx="4" fill="#1e293b" stroke="#0ea5e9" />
                </g>

                <!-- Power Pulse -->
                <circle r="3" fill="#38bdf8">
                    <animateMotion dur="3s" repeatCount="indefinite" path="M 100 250 C 120 300 150 400 250 450 L 250 650" />
                </circle>
            </svg>

            <!-- Holographic Info -->
            <div class="absolute top-4 left-4 font-mono text-[10px] space-y-1">
                <div class="text-sky-400">OUTPUT: <span x-text="totalKw"></span> kW</div>
                <div class="text-sky-400">CHARGE: 85%</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="space-y-6">
            <div>
                <label class="text-[10px] text-sky-500 font-mono uppercase tracking-widest mb-3 block italic">Architectural Logic</label>
                <div class="flex gap-2">
                    <template x-for="type in ['organic', 'geometric', 'minimalist']">
                        <button @click="branchType = type"
                                :class="branchType === type ? 'bg-sky-500 text-black border-sky-400' : 'bg-transparent text-sky-500 border-sky-500/30'"
                                class="flex-1 py-3 text-[10px] border rounded font-black uppercase transition-all"
                                x-text="type"></button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-3">
                    <label class="text-[10px] text-sky-500 font-mono uppercase italic block">Height Scaling</label>
                    <input type="range" x-model="height" min="300" max="800" class="w-full accent-sky-500">
                    <div class="text-[10px] text-right text-sky-400 font-mono" x-text="height + ' cm'"></div>
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] text-sky-500 font-mono uppercase italic block">Photon Collectors</label>
                    <input type="number" x-model="panels" class="w-full bg-black/40 border border-sky-500/20 rounded px-3 py-2 text-sm text-sky-400 outline-none">
                </div>
            </div>

            <div class="space-y-4 pt-4 border-t border-sky-500/10">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-mono uppercase tracking-wider text-sky-300">Energy Storage (kWh)</span>
                    <select x-model="batteryCap" class="bg-black border border-sky-500/20 rounded text-[10px] py-1 px-2 text-sky-400">
                        <option value="10">LFP 10kWh</option>
                        <option value="20">LFP 20kWh</option>
                        <option value="50">Solid-State 50kWh</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" x-model="nightLighting" class="hidden">
                        <div class="w-4 h-4 rounded border border-sky-500 flex items-center justify-center transition-all"
                             :class="nightLighting ? 'bg-sky-500 shadow-[0_0_10px_#0ea5e9]' : ''">
                             <span x-show="nightLighting" class="text-black text-[10px] font-bold">✓</span>
                        </div>
                        <span class="text-[10px] uppercase font-mono group-hover:text-sky-400">Aura Lighting</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" x-model="usbCharging" class="hidden">
                        <div class="w-4 h-4 rounded border border-sky-500 flex items-center justify-center transition-all"
                             :class="usbCharging ? 'bg-sky-500 shadow-[0_0_10px_#0ea5e9]' : ''">
                             <span x-show="usbCharging" class="text-black text-[10px] font-bold">✓</span>
                        </div>
                        <span class="text-[10px] uppercase font-mono group-hover:text-sky-400">USB-C Hub</span>
                    </label>
                </div>
            </div>

            <button class="w-full mt-4 bg-sky-500 hover:bg-sky-400 text-black font-black py-4 rounded-xl shadow-[0_0_25px_#0ea5e9] transition-all flex items-center justify-center gap-3 uppercase italic tracking-tighter overflow-hidden relative">
                <div class="absolute inset-0 bg-white/20 -translate-x-full hover:translate-x-full transition-transform duration-700"></div>
                <span>Sync to Power-Grid</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </button>
        </div>
    </div>
</div>
