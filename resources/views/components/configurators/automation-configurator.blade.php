@php
    /** @var \App\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
    $tenantId = tenant()->id ?? 'default';
@endphp

@props(['template'])

<div x-data="{
    config: { protocol: 'Matter', area: 120, latency: 15 },
    features: [
        { id: 'light', name: 'Adaptive Light', icon: 'LUM', active: true, price: 45000 },
        { id: 'temp', name: 'Climate Matrix', icon: 'TMP', active: true, price: 65000 },
        { id: 'sec', name: 'Neural Shield', icon: 'SEC', active: false, price: 85000 },
        { id: 'media', name: 'Audiophilic Sync', icon: 'AV', active: false, price: 120000 }
    ],
    correlationId: '{{ $correlationId }}',

    get activeNodes() {
        return this.features.filter(f => f.active).length * 12 + Math.floor(this.area / 10);
    },

    get totalPrice() {
        let base = 50000;
        let areaCost = this.area * 1200;
        let featuresCost = this.features.filter(f => f.active).reduce((sum, f) => sum + f.price, 0);
        let protocolMultiplier = this.config.protocol === 'KNX' ? 1.4 : 1.1;
        return Math.round((base + areaCost + featuresCost) * protocolMultiplier);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" 
class="relative group p-8 bg-slate-950/60 backdrop-blur-2xl rounded-3xl border border-sky-500/20 shadow-[0_0_50px_-12px_rgba(14,165,233,0.3)] overflow-hidden transition-all duration-500 animate-in fade-in zoom-in-95"
data-correlation-id="{{ $correlationId }}"
data-tenant-id="{{ $tenantId }}">
    
    <!-- GRID HUD OVERLAY -->
    <div class="absolute inset-0 pointer-events-none opacity-20" 
         style="background-image: radial-gradient(#0ea5e9 0.5px, transparent 0.5px); background-size: 32px 32px;"></div>
    
    <!-- TECH SCANLINE -->
    <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-sky-500/50 to-transparent top-0 animate-scanline"></div>

    <div class="relative flex flex-col lg:flex-row gap-12">
        
        <!-- LEFT PANEL: NEURAL VIZ HUD -->
        <div class="inline-size-full lg:inline-size-3/5 bg-black/40 rounded-3xl min-block-size-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden group/canvas ring-1 ring-white/10">
            
            <!-- STATUS CHIPS -->
            <div class="absolute inset-block-start-8 inset-inline-start-8 z-20 flex flex-col gap-3 font-mono text-left">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur-md px-4 py-1.5 rounded-full border border-sky-500/30 text-[10px] text-sky-400 uppercase font-black animate-pulse">
                    <span class="block-size-1.5 inline-size-1.5 rounded-full bg-sky-500 shadow-[0_0_8px_#0ea5e9]"></span>
                    Neuro-Bridge Engine v.2026
                </div>
                <div class="bg-indigo-500/10 backdrop-blur-md px-3 py-1 rounded-md border border-indigo-500/20 text-[9px] text-indigo-300 font-mono tracking-tighter">
                    CORRELATION_ID: {{ substr($correlationId, 0, 8) }}
                </div>
            </div>

            <!-- Visualization Metrics -->
            <div class="absolute inset-block-end-8 inset-inline-end-8 z-20 flex flex-col items-end gap-1 font-mono text-[10px] text-slate-500 uppercase tracking-widest text-right">
                <div x-text="'LATENCY: ' + config.latency + ' MS'"></div>
                <div x-text="'ACTIVE_NODES: ' + activeNodes + ' U'"></div>
                <div x-text="'ECOSYSTEM: ' + config.protocol"></div>
            </div>

            <!-- SVG Grid Visualization -->
            <div class="relative inline-size-full max-inline-size-md aspect-square group-hover/canvas:scale-[1.05] transition-transform duration-700">
                <svg viewBox="0 0 600 600" class="inline-size-full opacity-60 drop-shadow-[0_0_30px_rgba(14,165,233,0.2)]">
                    <defs>
                        <radialGradient id="nodeGlow">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.4" />
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0" />
                        </radialGradient>
                    </defs>

                    <!-- Background Grid Loops -->
                    <circle cx="300" cy="300" r="100" stroke="#0ea5e9" stroke-width="0.5" fill="none" opacity="0.1" />
                    <circle cx="300" cy="300" r="200" stroke="#0ea5e9" stroke-width="0.5" fill="none" opacity="0.1" />

                    <!-- Nodes & Connections -->
                    <g>
                        <template x-for="i in 12">
                            <g>
                                <line x1="300" y1="300" 
                                      :x2="300 + 220 * Math.cos(i * Math.PI / 6)" 
                                      :y2="300 + 220 * Math.sin(i * Math.PI / 6)" 
                                      stroke="#38bdf8" stroke-width="0.5" opacity="0.2" />
                                <circle :cx="300 + 220 * Math.cos(i * Math.PI / 6)" 
                                        :cy="300 + 220 * Math.sin(i * Math.PI / 6)" 
                                        r="6" fill="url(#nodeGlow)" />
                                <circle :cx="300 + 220 * Math.cos(i * Math.PI / 6)" 
                                        :cy="300 + 220 * Math.sin(i * Math.PI / 6)" 
                                        r="3" fill="#38bdf8">
                                    <animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite" :begin="i*0.2" />
                                </circle>
                            </g>
                        </template>
                    </g>

                    <!-- Master Controller -->
                    <rect x="270" y="270" width="60" height="60" rx="12" fill="rgba(14,165,233,0.1)" stroke="#38bdf8" stroke-width="2" />
                    <circle cx="300" cy="300" r="15" fill="#38bdf8" opacity="0.2" class="animate-pulse" />
                </svg>
                
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-7xl font-black text-white italic tracking-tighter leading-none shadow-xl block" x-text="activeNodes"></span>
                    <span class="text-[10px] text-sky-500 font-black tracking-[0.4em] mt-4 uppercase">Sync Nodes Active</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INDUSTRIAL CONTROLS -->
        <div class="inline-size-full lg:inline-size-2/5 flex flex-col space-y-10">
            <div class="space-y-4 text-left">
                <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">
                    Neuro <span class="text-sky-500 underline decoration-4 underline-offset-8 decoration-sky-500/50 text-left">Home</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-mono uppercase tracking-[0.3em] text-left">Autonomous Logic Matrix</p>
            </div>

            <div class="space-y-8 flex-grow overflow-y-auto max-block-size-[500px] pr-4 custom-scrollbar text-left">
                
                <!-- OPERATIONAL AREA -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 px-1">
                        <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block italic">Spatial Area (m2)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none" x-text="config.area"></span>
                    </div>
                    <input type="range" x-model="config.area" min="20" max="600" step="10" 
                           class="inline-size-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-sky-500 transition-all">
                </div>

                <!-- ECOSYSTEM PROTOCOL -->
                <div class="grid grid-cols-2 gap-4">
                    <template x-for="p in ['Matter', 'KNX']">
                        <button @click="config.protocol = p" 
                                :class="config.protocol === p ? 'bg-sky-600/20 border-sky-500 text-sky-400' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'"
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl hover:scale-[1.02]">
                            <span x-text="p"></span>
                        </button>
                    </template>
                </div>

                <!-- NEURAL FEATURE CLUSTERS -->
                <div class="space-y-4 pt-4 border-block-start border-white/10">
                    <template x-for="feature in features" :key="feature.id">
                        <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group cursor-pointer hover:border-sky-500 transition-all border-inline-start-4" 
                             :class="feature.active ? 'border-inline-start-sky-500' : 'border-inline-start-transparent'"
                             @click="feature.active = !feature.active">
                            <div class="text-left">
                                <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase" x-text="feature.name"></span>
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2" x-text="feature.icon + ' Logic Cluster'"></span>
                            </div>
                            <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" 
                                 :class="feature.active ? 'bg-sky-600 shadow-[0_0_15px_#0ea5e9]' : 'bg-slate-800'">
                                <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" 
                                     :style="feature.active ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- CORE BUDGETARY OUTPUT -->
            <div class="bg-sky-600/5 p-10 rounded-[3.5rem] border border-sky-500/20 space-y-6 font-mono relative overflow-hidden transition-all text-left border-t-4 border-sky-500">
                <div class="absolute inset-0 pointer-events-none opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] text-left"></div>
                
                <div class="flex justify-between items-end border-b border-sky-500/10 pb-6 relative z-10 text-left">
                    <span class="text-slate-500 font-bold uppercase text-[11px] tracking-[0.3em] text-left">Core Allocation:</span>
                    <span class="text-white font-black italic text-xl" x-text="formatPrice(totalPrice)"></span>
                </div>
                
                <button @click="saveProject()" class="group relative inline-size-full py-7 bg-sky-600 text-slate-950 rounded-2xl font-black uppercase tracking-widest text-xs overflow-hidden transition-all hover:bg-sky-500 hover:scale-[1.01] active:scale-[0.98] shadow-[0_20px_40px_-15px_rgba(14,165,233,0.4)]">
                    <span class="relative z-10 flex items-center justify-center gap-4 text-left">
                        Initiate Neural Link
                        <svg class="block-size-5 inline-size-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                </button>
            </div>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold">Base Neuro-Core v26 Deployment. Compatible with KNX, Matter, ZigBee. 100% On-Device Neural Processing.</p>
        </div>
    </div>
</div>
