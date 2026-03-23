<div x-data="{
    area: 500,
    zones: 4,
    source: 'tank', // well, mains, tank
    automation: 'ai', // timer, sensor, ai
    filtration: true,
    fertigation: false,
    correlationId: '{{ Str::uuid() }}',
    
    get totalEmitters() { return this.zones * 12; },
    get price() { 
        let base = this.area * 150;
        base += this.zones * 12000;
        if (this.automation === 'ai') base += 45000;
        if (this.fertigation) base += 25000;
        return Math.round(base);
    }
}" class="p-8 bg-[#0d0d0d] border border-blue-500/20 rounded-3xl text-white shadow-2xl relative overflow-hidden group">
    
    <!-- Water Caustic Effect -->
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
        <svg width="100%" height="100%">
            <filter id="water-tex">
                <feTurbulence type="fractalNoise" baseFrequency="0.02" numOctaves="3" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="20" />
            </filter>
            <rect width="100%" height="100%" filter="url(#water-tex)" fill="blue" />
        </svg>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center mb-10 relative z-10">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-500/10 rounded-xl border border-blue-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            <div>
                <h3 class="text-2xl font-black text-blue-400 tracking-tight italic uppercase">AQUA-MIND v4</h3>
                <p class="text-[9px] font-mono text-blue-500/60 uppercase tracking-[0.4em]">Precision Irrigation Intelligence</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold text-blue-400" x-text="price.toLocaleString() + ' ₽'"></div>
            <div class="text-[9px] font-mono text-blue-500/30">MODULE: {{ $uuid }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Dashboard Simulation -->
        <div class="bg-blue-950/20 border border-blue-500/10 rounded-2xl p-6 relative overflow-hidden h-[350px]">
            <svg viewBox="0 0 400 300" class="w-full h-full">
                <!-- Zone Visualization -->
                <template x-for="z in Number(zones)">
                    <g :transform="'translate(' + ((z-1)*80 + 40) + ', 50)'">
                        <circle cx="0" cy="0" r="15" fill="none" stroke="#3b82f6" stroke-width="1" />
                        <circle cx="0" cy="0" r="2" fill="#3b82f6">
                            <animate attributeName="r" values="2;8;2" dur="2s" :begin="z*0.5 + 's'" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="1;0;1" dur="2s" :begin="z*0.5 + 's'" repeatCount="indefinite" />
                        </circle>
                        <text x="0" y="30" text-anchor="middle" fill="#3b82f6" font-size="8" font-family="monospace" 
                              x-text="'ZONE ' + z"></text>
                    </g>
                </template>

                <!-- Flow Graph -->
                <path d="M 50 250 L 350 250 M 50 150 L 50 250" stroke="#3b82f6" stroke-width="0.5" opacity="0.3" />
                <path d="M 50 230 Q 100 180 150 210 T 250 160 T 350 190" fill="none" stroke="#2563eb" stroke-width="2">
                    <animate attributeName="stroke-dasharray" from="0,1000" to="1000,0" dur="5s" repeatCount="indefinite" />
                </path>
                
                <!-- HUD Labels -->
                <g font-family="monospace" font-size="7" fill="#60a5fa">
                    <text x="300" y="140">PRESSURE: 3.2 BAR</text>
                    <text x="300" y="155" x-show="filtration">FILTER: 98% OK</text>
                    <text x="300" y="170" x-show="fertigation" class="text-emerald-400">FERT: 0.5%</text>
                </g>
            </svg>
            
            <div class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-blue-500/10 to-transparent"></div>
        </div>

        <!-- Inputs -->
        <div class="space-y-6 self-center">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase font-mono text-blue-500/50">Irrigation Area (m2)</label>
                    <input type="number" x-model="area" class="w-full bg-blue-500/5 border border-blue-500/20 rounded px-4 py-3 text-lg font-bold text-blue-400 outline-none focus:border-blue-500 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase font-mono text-blue-500/50">Active Zones</label>
                    <select x-model="zones" class="w-full bg-blue-500/5 border border-blue-500/20 rounded px-4 py-3 text-lg font-bold text-blue-400 outline-none">
                        <option value="2">2 Zones</option>
                        <option value="4">4 Zones</option>
                        <option value="8">8 Zones</option>
                        <option value="16">16 Zones + Ultra</option>
                    </select>
                </div>
            </div>

            <div class="space-y-3">
                <span class="text-[10px] uppercase font-mono text-blue-500/50 block italic">Logic Processor</span>
                <div class="flex bg-blue-500/5 rounded-lg p-1 border border-blue-500/10">
                    <template x-for="item in ['timer', 'sensor', 'ai']">
                        <button @click="automation = item"
                                :class="automation === item ? 'bg-blue-600 text-white' : 'text-blue-500/60 hover:text-blue-400'"
                                class="flex-1 py-2 text-[10px] font-black uppercase rounded-md transition-all active:scale-95"
                                x-text="item"></button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button @click="filtration = !filtration"
                        :class="filtration ? 'border-blue-500 bg-blue-500/10 text-blue-400' : 'border-slate-800 text-slate-600'"
                        class="border py-3 rounded text-[10px] font-mono font-bold uppercase transition-all">
                    Multi-Filtration
                </button>
                <button @click="fertigation = !fertigation"
                        :class="fertigation ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400' : 'border-slate-800 text-slate-600'"
                        class="border py-3 rounded text-[10px] font-mono font-bold uppercase transition-all">
                    Fertigation AI
                </button>
            </div>

            <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl shadow-[0_15px_30px_rgba(37,99,235,0.3)] transition-all flex items-center justify-center gap-4 group">
                <span class="text-lg italic tracking-tighter">EXECUTE FLOW SYSTEM</span>
                <div class="w-10 h-px bg-white/30 group-hover:w-16 transition-all"></div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </button>
        </div>
    </div>
</div>
