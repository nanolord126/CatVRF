@php
    /** @var \App\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
    $tenantId = tenant()->id ?? 'default';
@endphp

@props(['template'])

<div x-data="{
    config: { protocol: 'Matter', nodeCount: 32, hubType: 'Local', lighting: true, security: true, climate: false },
    correlationId: '{{ $correlationId }}',

    get reliability() {
        return this.config.protocol === 'KNX' ? 99.9 : (this.config.hubType === 'Local' ? 98.5 : 95.2);
    },

    get totalPrice() {
        let baseHub = this.config.hubType === 'Local' ? 45000 : (this.config.hubType === 'Industrial' ? 125000 : 15000);
        let nodeCost = this.config.nodeCount * 4500;
        let softwareCost = 25000;
        let modulesCost = (this.config.lighting ? 35000 : 0) + (this.config.security ? 55000 : 0) + (this.config.climate ? 40000 : 0);
        return Math.round(baseHub + nodeCost + softwareCost + modulesCost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" 
class="relative group p-8 bg-slate-950/60 backdrop-blur-2xl rounded-3xl border border-amber-500/20 shadow-[0_0_50px_-12px_rgba(217,119,6,0.3)] overflow-hidden transition-all duration-500 animate-in fade-in zoom-in-95"
data-correlation-id="{{ $correlationId }}"
data-tenant-id="{{ $tenantId }}">
    
    <!-- GRID HUD OVERLAY -->
    <div class="absolute inset-0 pointer-events-none opacity-20" 
         style="background-image: radial-gradient(#d97706 0.5px, transparent 0.5px); background-size: 32px 32px;"></div>
    
    <!-- TECH SCANLINE -->
    <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-500/50 to-transparent top-0 animate-scanline"></div>

    <div class="relative flex flex-col lg:flex-row gap-12">
        
        <!-- LEFT PANEL: NEURAL VIZ HUD -->
        <div class="inline-size-full lg:inline-size-3/5 bg-black/40 rounded-3xl min-block-size-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden group/canvas ring-1 ring-white/10">
            
            <!-- STATUS CHIPS -->
            <div class="absolute inset-block-start-8 inset-inline-start-8 z-20 flex flex-col gap-3 font-mono">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur-md px-4 py-1.5 rounded-full border border-amber-500/30 text-[10px] text-amber-400 uppercase font-black animate-pulse">
                    <span class="block-size-1.5 inline-size-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_#d97706]"></span>
                    Neural-Core Engine v.2026
                </div>
                <div class="bg-indigo-500/10 backdrop-blur-md px-3 py-1 rounded-md border border-indigo-500/20 text-[9px] text-indigo-300 font-mono tracking-tighter">
                    CORRELATION_ID: {{ substr($correlationId, 0, 8) }}
                </div>
            </div>

            <div class="absolute inset-block-end-8 inset-inline-end-8 z-20 flex flex-col items-end gap-1 font-mono text-[10px] text-slate-500 uppercase">
                <div x-text="'PROTOCOL: ' + config.protocol"></div>
                <div x-text="'NODES: ' + config.nodeCount"></div>
                <div x-text="'RELIABILITY: ' + reliability + '%'"></div>
            </div>

            <!-- Topology Visualization -->
            <div class="relative inline-size-full max-inline-size-md aspect-square group-hover/canvas:scale-[1.05] transition-transform duration-700">
                <svg viewBox="0 0 400 400" class="inline-size-full opacity-60 drop-shadow-[0_0_30px_rgba(217,119,6,0.2)]">
                    <defs>
                        <radialGradient id="hubGlow">
                            <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.3" />
                            <stop offset="100%" stop-color="#fbbf24" stop-opacity="0" />
                        </radialGradient>
                    </defs>

                    <!-- Central Hub -->
                    <circle cx="200" cy="200" r="60" fill="url(#hubGlow)" class="animate-pulse" />
                    <rect x="175" y="175" width="50" height="50" rx="12" fill="rgba(180,83,9,0.2)" stroke="#f59e0b" stroke-width="2" class="animate-pulse" />
                    
                    <!-- Nodes -->
                    <template x-for="i in 16">
                        <g>
                            <line x1="200" y1="200" 
                                  :x2="200 + 150 * Math.cos(i * Math.PI / 8)" 
                                  :y2="200 + 150 * Math.sin(i * Math.PI / 8)" 
                                  stroke="#f59e0b" stroke-width="0.5" stroke-opacity="0.2" />
                            <circle :cx="200 + 150 * Math.cos(i * Math.PI / 8)" 
                                    :cy="200 + 150 * Math.sin(i * Math.PI / 8)" 
                                    r="4" fill="#f59e0b" fill-opacity="0.6">
                                <animate attributeName="r" values="3;5;3" dur="2s" repeatCount="indefinite" :begin="i*0.1" />
                            </circle>
                        </g>
                    </template>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-7xl font-black text-white italic tracking-tighter leading-none shadow-xl block" x-text="reliability + '%'"></span>
                    <span class="text-[10px] text-amber-500 font-black tracking-[0.4em] mt-4 uppercase">Network Uptime</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INDUSTRIAL CONTROLS -->
        <div class="inline-size-full lg:inline-size-2/5 flex flex-col space-y-10">
            <div class="space-y-4">
                <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">
                    Smart <span class="text-amber-500 underline decoration-4 underline-offset-8 decoration-amber-500/50">Neural</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-mono uppercase tracking-[0.3em]">Home Logic Matrix Topology</p>
            </div>

            <div class="space-y-8 flex-grow overflow-y-auto max-block-size-[500px] pr-4 custom-scrollbar">
                
                <!-- NODE SATURATION -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 px-1">
                        <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block italic">Edge Nodes Saturation</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none" x-text="config.nodeCount"></span>
                    </div>
                    <input type="range" x-model="config.nodeCount" min="8" max="150" step="4" 
                           class="inline-size-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-amber-500 transition-all">
                </div>

                <!-- PROTOCOL SELECTION -->
                <div class="grid grid-cols-2 gap-4">
                    <template x-for="p in ['Matter', 'KNX']">
                        <button @click="config.protocol = p" 
                                :class="config.protocol === p ? 'bg-amber-600/20 border-amber-500 text-amber-400' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'"
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl hover:scale-[1.02]">
                            <span x-text="p"></span>
                        </button>
                    </template>
                </div>

                <!-- SUBSYSTEM CLUSTERS -->
                <div class="space-y-4 pt-4 border-block-start border-white/10">
                    <!-- Lighting -->
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group cursor-pointer hover:border-amber-500 transition-all border-inline-start-4" 
                         :class="config.lighting ? 'border-inline-start-amber-500' : 'border-inline-start-transparent'"
                         @click="config.lighting = !config.lighting">
                        <div class="text-left">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase">Illumination Grid</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2">Dynamic Dimming Cluster</span>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" 
                             :class="config.lighting ? 'bg-amber-600 shadow-[0_0_15px_#f59e0b]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" 
                                 :style="config.lighting ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group cursor-pointer hover:border-amber-500 transition-all border-inline-start-4" 
                         :class="config.security ? 'border-inline-start-amber-500' : 'border-inline-start-transparent'"
                         @click="config.security = !config.security">
                        <div class="text-left">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase">AI Shield Layer</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2">Neural Pattern Protection</span>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" 
                             :class="config.security ? 'bg-amber-600 shadow-[0_0_15px_#f59e0b]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" 
                                 :style="config.security ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CORE BUDGETARY OUTPUT -->
            <div class="bg-amber-600/5 p-10 rounded-[3.5rem] border border-amber-500/20 space-y-6 font-mono relative overflow-hidden transition-all">
                <div class="absolute inset-0 pointer-events-none opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="flex justify-between items-end border-b border-amber-500/10 pb-6 relative z-10">
                    <span class="text-slate-500 font-bold uppercase text-[11px] tracking-[0.3em]">Synapse Allocation:</span>
                    <span class="text-white font-black italic text-xl" x-text="formatPrice(totalPrice)"></span>
                </div>
                
                <button @click="saveProject()" class="group relative inline-size-full py-7 bg-amber-600 text-slate-950 rounded-2xl font-black uppercase tracking-widest text-xs overflow-hidden transition-all hover:bg-amber-500 hover:scale-[1.01] active:scale-[0.98] shadow-[0_20px_40px_-15px_rgba(245,158,11,0.4)]">
                    <span class="relative z-10 flex items-center justify-center gap-4">
                        Initiate Network Sync
                        <svg class="block-size-5 inline-size-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter">
                <div class="inline-block px-5 py-2 rounded-full bg-amber-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none">Logic Matrix Topology</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Smart Neural</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest">Device Nodes (Count)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none" x-text="config.nodeCount"></span>
                    </div>
                    <input type="range" x-model="config.nodeCount" min="8" max="150" step="4" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-amber-500 transition-all font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                    <template x-for="p in ['Matter', 'KNX']">
                        <button @click="config.protocol = p" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic":class="config.protocol === p ? 'bg-amber-700 text-white border-amber-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="p"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-amber-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">Lighting Control Grid</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase">Dynamic Dimming Cluster</span>
                        </div>
                        <button @click="config.lighting = !config.lighting" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="config.lighting ? 'bg-amber-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :style="config.lighting ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-amber-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter italic font-sans italic tracking-tighter">AI Security Shield</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase">Neural Pattern Protection</span>
                        </div>
                        <button @click="config.security = !config.security" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :class="config.security ? 'bg-amber-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic" :style="config.security ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter">
                <div class="absolute inset-x-0 h-[1px] bg-amber-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                    <div class="font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                        <span class="text-[12px] text-amber-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">Smart-Core Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-amber-700 hover:bg-amber-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                    <span>Initiate Network Sync</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic uppercase italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase font-sans italic tracking-tighter leading-none italic uppercase">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
