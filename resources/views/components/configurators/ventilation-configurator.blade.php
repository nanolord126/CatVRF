@php
    /** @var \App\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
    $tenantId = tenant()->id ?? 'default';
@endphp

@props(['template'])

<div x-data="{
    config: { airflow: 1200, recoveryType: 'Rotary', mode: 'Auto', uvc: true, humidifier: false, active: true },
    correlationId: '{{ $correlationId }}',

    get results() {
        return {
            efficiency: this.config.recoveryType === 'Rotary' ? 88 : (this.config.recoveryType === 'Plate' ? 75 : 92),
            noiseLevel: Math.round(35 + (this.config.airflow / 100)),
        };
    },

    get totalPrice() {
        let base = 120000;
        let flowCost = this.config.airflow * 150;
        let recoveryBonus = this.config.recoveryType === 'Rotary' ? 45000 : (this.config.recoveryType === 'Plate' ? 25000 : 75000);
        let options = (this.config.uvc ? 35000 : 0) + (this.config.humidifier ? 55000 : 0);
        return Math.round(base + flowCost + recoveryBonus + options);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" 
class="relative group p-8 bg-slate-950/60 backdrop-blur-2xl rounded-3xl border border-cyan-500/20 shadow-[0_0_50px_-12px_rgba(6,182,212,0.3)] overflow-hidden transition-all duration-500 animate-in fade-in zoom-in-95"
data-correlation-id="{{ $correlationId }}"
data-tenant-id="{{ $tenantId }}">
    
    <!-- GRID HUD OVERLAY -->
    <div class="absolute inset-0 pointer-events-none opacity-20" 
         style="background-image: radial-gradient(#0891b2 0.5px, transparent 0.5px); background-size: 32px 32px;"></div>
    
    <!-- TECH SCANLINE -->
    <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-cyan-500/50 to-transparent top-0 animate-scanline"></div>

    <div class="relative flex flex-col lg:flex-row gap-12">
        
        <!-- LEFT PANEL: AIRFLOW VIZ HUD -->
        <div class="inline-size-full lg:inline-size-3/5 bg-black/40 rounded-3xl min-block-size-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden group/canvas ring-1 ring-white/10">
            
            <!-- STATUS CHIPS -->
            <div class="absolute inset-block-start-8 inset-inline-start-8 z-20 flex flex-col gap-3 font-mono">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur-md px-4 py-1.5 rounded-full border border-cyan-500/30 text-[10px] text-cyan-400 uppercase font-black animate-pulse">
                    <span class="block-size-1.5 inline-size-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_#0891b2]"></span>
                    Pure-Stream Engine v.2026
                </div>
                <div class="bg-indigo-500/10 backdrop-blur-md px-3 py-1 rounded-md border border-indigo-500/20 text-[9px] text-indigo-300 font-mono tracking-tighter">
                    CORRELATION_ID: {{ substr($correlationId, 0, 8) }}
                </div>
            </div>

            <div class="absolute inset-block-end-8 inset-inline-end-8 z-20 flex flex-col items-end gap-1 font-mono text-[10px] text-slate-500 uppercase tracking-widest text-right">
                <div x-text="'EFFICIENCY: ' + results.efficiency + '%'"></div>
                <div x-text="'NOISE: ' + results.noiseLevel + ' DB'"></div>
                <div x-text="'FLOW: ' + config.airflow + ' M3/H'"></div>
            </div>

            <!-- Ductwork Visualization -->
            <div class="relative inline-size-full max-inline-size-md aspect-square group-hover/canvas:scale-[1.05] transition-transform duration-700">
                <svg viewBox="0 0 500 500" class="inline-size-full opacity-60 drop-shadow-[0_0_30px_rgba(6,182,212,0.2)]">
                    <defs>
                        <linearGradient id="ductGlow" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#0891b2" stop-opacity="0.1" />
                            <stop offset="50%" stop-color="#22d3ee" stop-opacity="0.4" />
                            <stop offset="100%" stop-color="#0891b2" stop-opacity="0.1" />
                        </linearGradient>
                    </defs>

                    <!-- Main Duct -->
                    <path d="M 50 250 L 450 250" stroke="url(#ductGlow)" stroke-width="45" fill="none" stroke-linecap="round" />
                    <path d="M 150 250 L 150 100" stroke="#0891b2" stroke-width="35" fill="none" stroke-linecap="round" opacity="0.4" />
                    <path d="M 350 250 L 350 400" stroke="#0891b2" stroke-width="35" fill="none" stroke-linecap="round" opacity="0.4" />
                    
                    <!-- Recovery Unit -->
                    <rect x="210" y="200" width="80" height="100" fill="rgba(6,182,212,0.1)" stroke="#22d3ee" stroke-width="2" rx="10" />
                    <path d="M 230 220 L 270 280 M 270 220 L 230 280" stroke="#22d3ee" stroke-width="2" opacity="0.5" />

                    <!-- Airflow Particles -->
                    <template x-if="config.active">
                        <g>
                            <template x-for="i in 12">
                                <circle r="3" fill="#22d3ee" class="shadow-glow">
                                    <animateTransform attributeName="transform" type="translate" 
                                                      :from="20 + i * 40 + ' 250'" 
                                                      :to="420 + i * 40 + ' 250'" 
                                                      dur="1.2s" repeatCount="indefinite" />
                                </circle>
                            </template>
                        </g>
                    </template>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-7xl font-black text-white italic tracking-tighter leading-none shadow-xl block" x-text="results.efficiency + '%'"></span>
                    <span class="text-[10px] text-cyan-500 font-black tracking-[0.4em] mt-4 uppercase">Heat Recovery</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INDUSTRIAL CONTROLS -->
        <div class="inline-size-full lg:inline-size-2/5 flex flex-col space-y-10">
            <div class="space-y-4">
                <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">
                    Air <span class="text-cyan-500 underline decoration-4 underline-offset-8 decoration-cyan-500/50">Core</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-mono uppercase tracking-[0.3em]">Hepa-Flow Supply Matrix</p>
            </div>

            <div class="space-y-8 flex-grow overflow-y-auto max-block-size-[500px] pr-4 custom-scrollbar">
                
                <!-- AIRFLOW INTENSITY -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 px-1">
                        <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block italic">Flow Saturation (m3/h)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none" x-text="config.airflow"></span>
                    </div>
                    <input type="range" x-model="config.airflow" min="300" max="4500" step="100" 
                           class="inline-size-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-cyan-500 transition-all">
                </div>

                <!-- RECOVERY CLUSTERS -->
                <div class="grid grid-cols-2 gap-4">
                    <template x-for="r in ['Rotary', 'Plate', 'Enthalpy']">
                        <button @click="config.recoveryType = r" 
                                :class="config.recoveryType === r ? 'bg-cyan-600/20 border-cyan-500 text-cyan-400' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'"
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl hover:scale-[1.02]">
                            <span x-text="r"></span>
                        </button>
                    </template>
                </div>

                <!-- AIR QUALITY SUBSYSTEMS -->
                <div class="space-y-4 pt-4 border-block-start border-white/10">
                    <!-- UVC -->
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group cursor-pointer hover:border-cyan-500 transition-all border-inline-start-4" 
                         :class="config.uvc ? 'border-inline-start-cyan-500' : 'border-inline-start-transparent'"
                         @click="config.uvc = !config.uvc">
                        <div class="text-left">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase">UV-C Shard</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2">Dna Destruction Layer</span>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" 
                             :class="config.uvc ? 'bg-cyan-600 shadow-[0_0_15px_#06b6d4]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" 
                                 :style="config.uvc ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>

                    <!-- Humidifier -->
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group cursor-pointer hover:border-cyan-500 transition-all border-inline-start-4" 
                         :class="config.humidifier ? 'border-inline-start-cyan-500' : 'border-inline-start-transparent'"
                         @click="config.humidifier = !config.humidifier">
                        <div class="text-left">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase">Nexus Mist</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2">Ultrasonic Humidity Sync</span>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" 
                             :class="config.humidifier ? 'bg-cyan-600 shadow-[0_0_15px_#06b6d4]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" 
                                 :style="config.humidifier ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CORE BUDGETARY OUTPUT -->
            <div class="bg-cyan-600/5 p-10 rounded-[3.5rem] border border-cyan-500/20 space-y-6 font-mono relative overflow-hidden transition-all">
                <div class="absolute inset-0 pointer-events-none opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="flex justify-between items-end border-b border-cyan-500/10 pb-6 relative z-10">
                    <span class="text-slate-500 font-bold uppercase text-[11px] tracking-[0.3em]">Module Allocation:</span>
                    <span class="text-white font-black italic text-xl" x-text="formatPrice(totalPrice)"></span>
                </div>
                
                <button @click="saveProject()" class="group relative inline-size-full py-7 bg-cyan-600 text-slate-950 rounded-2xl font-black uppercase tracking-widest text-xs overflow-hidden transition-all hover:bg-cyan-500 hover:scale-[1.01] active:scale-[0.98] shadow-[0_20px_40px_-15px_rgba(6,182,212,0.4)]">
                    <span class="relative z-10 flex items-center justify-center gap-4">
                        Initiate Air Sync
                        <svg class="block-size-5 inline-size-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
