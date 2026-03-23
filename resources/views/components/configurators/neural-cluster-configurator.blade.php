@php
    declare(strict_types=1);
@endphp

<div x-data="{
    fleetSize: 200,
    serverDensity: 'High',
    liquidCooling: true,
    h100Gpu: 32,
    renewableEnergy: 80,
    
    get tflops() {
        return this.h100Gpu * 2000;
    },
    
    get estimatedCost() {
        let base = this.h100Gpu * 4500000;
        if (this.liquidCooling) base += 25000000;
        base += this.fleetSize * 50000; 
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px]">
        
        <!-- Visual: AI Cluster Simulation -->
        <div class="relative bg-black rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl">
            <!-- HUD -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/80 backdrop-blur-2xl px-5 py-2.5 rounded-full border border-indigo-500/40 shadow-[0_0_40px_rgba(99,102,241,0.2)]">
                    <div class="w-2.5 h-2.5 bg-indigo-500 rounded-full animate-[ping_2s_linear_infinite]"></div>
                    <span class="text-[10px] text-indigo-400 font-black uppercase tracking-[0.3em] italic tracking-tighter leading-none">Compute-Core v.2026 Cluster</span>
                </div>
            </div>

            <!-- Visualization: Neural Rack Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_0%,rgba(99,102,241,0.15)_0%,transparent_80%)]">
                <!-- Data Flow Pulses -->
                <div class="absolute inset-x-0 h-[2px] bg-indigo-500/30 top-1/2 -translate-y-1/2 animate-[pulse_1s_infinite]"></div>
                
                <svg viewBox="0 0 400 400" class="w-full h-full drop-shadow-[0_0_120px_rgba(99,102,241,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Rack Enclosure -->
                    <rect x="50" y="50" width="300" height="300" fill="rgba(255,255,255,0.02)" stroke="rgba(99,102,241,0.4)" stroke-width="2" rx="20" />
                    
                    <!-- GPU Blades Pattern -->
                    <template x-for="row in 4">
                        <template x-for="col in 8">
                            <rect :x="70 + col*30" :y="70 + row*60" width="22" height="40" rx="3" 
                                  :fill="liquidCooling ? 'rgba(99,102,241,0.2)' : 'rgba(255,255,255,0.05)'" 
                                  class="transition-all duration-1000" />
                        </template>
                    </template>

                    <!-- Liquid Cooling Pipes -->
                    <g x-show="liquidCooling" class="transition-opacity duration-1000">
                        <path d="M 50 100 Q 200 80 350 100" fill="none" stroke="#6366f1" stroke-width="1.5" class="opacity-60" />
                        <path d="M 50 200 Q 200 180 350 200" fill="none" stroke="#6366f1" stroke-width="1.5" class="opacity-60" />
                        <path d="M 50 300 Q 200 280 350 300" fill="none" stroke="#6366f1" stroke-width="1.5" class="opacity-60" />
                    </g>
                </svg>

                <!-- Load Telemetry -->
                <div class="absolute bottom-16 inset-x-12 grid grid-cols-4 gap-4">
                    <template x-for="i in 4">
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 animate-[pulse_2s_infinite]" :style="`width: ${Math.random()*60 + 40}%`"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Perf Stats Overlay -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-20">
                <div class="bg-indigo-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-indigo-500/20">
                    <span class="text-[9px] text-indigo-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Compute Density</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="tflops + ' TFLOPS'"></span>
                </div>
                <div class="bg-indigo-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-indigo-500/20 text-center">
                    <span class="text-[9px] text-indigo-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">PUE Factor</span>
                    <span class="text-2xl text-emerald-400 font-black italic tracking-tighter">1.05</span>
                </div>
                <div class="bg-indigo-950/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-indigo-500/20 text-right">
                    <span class="text-[9px] text-indigo-400 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Clean Energy</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="renewableEnergy + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Infrastructure Spec -->
        <div class="bg-white/[0.02] p-8 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden group/controls">
            <!-- Digital Rain Background -->
            <div class="absolute inset-0 opacity-[0.03] select-none pointer-events-none text-indigo-500 overflow-hidden text-[8px] font-mono leading-none">
                <template x-for="i in 100">
                    <div x-text="Math.random().toString(36).substring(7)" class="inline-block px-1"></div>
                </template>
            </div>

            <div class="mb-14 relative z-10">
                <div class="inline-block px-4 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 mb-6 shadow-xl leading-none italic tracking-tighter">
                    <span class="text-[10px] text-indigo-400 font-black uppercase tracking-[0.3em] leading-none shadow-sm">AI Infrastructure Vertical</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none italic tracking-tighter shadow-sm leading-none">Neural HPC-Cluster</h3>
                <p class="text-[12px] text-slate-500 font-extrabold uppercase tracking-widest mt-6 opacity-80 leading-relaxed italic tracking-tighter leading-none shadow-sm italic shadow-sm leading-none">High-Density GPU Farm with Modular Liquid Cooling
                & Integrated AI-Autonomous Ops</p>
            </div>

            <div class="flex-grow space-y-12 relative z-10 px-4">
                <!-- GPU Count Slider -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-1 italic">NVIDIA H100 GPU Units</span>
                        <span class="text-3xl font-black text-indigo-500 italic tracking-tighter leading-none italic leading-none" x-text="h100Gpu"></span>
                    </div>
                    <div class="relative py-3">
                        <input type="range" x-model="h100Gpu" min="8" max="128" step="8" 
                               class="w-full h-2.5 bg-slate-900 rounded-full appearance-none cursor-pointer accent-indigo-500 shadow-inner">
                        <div class="absolute top-10 left-0 w-full flex justify-between px-1 opacity-20 transition-all group-hover/controls:opacity-40">
                             <template x-for="p in 8"><div class="w-1 h-4 bg-white rounded-full"></div></template>
                        </div>
                    </div>
                </div>

                <!-- Strategic Toggles -->
                <div class="grid grid-cols-1 gap-5">
                    <button @click="liquidCooling = !liquidCooling" 
                            class="flex items-center justify-between p-7 rounded-[2.5rem] border transition-all duration-500 relative group/btn overflow-hidden shadow-2xl"
                            :class="liquidCooling ? 'bg-indigo-500/10 border-indigo-500/40 ring-1 ring-indigo-500/20' : 'bg-white/5 border-white/10 opacity-70 hover:opacity-100'">
                        <div class="flex items-center space-x-6 relative z-10 leading-none">
                            <div class="w-16 h-16 rounded-3xl flex items-center justify-center transition-all shadow-xl border border-white/5" :class="liquidCooling ? 'bg-indigo-600 text-slate-950 scale-110 -rotate-3' : 'bg-slate-800 text-white/20'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <div class="text-left leading-none">
                                <span class="text-lg font-black block text-white italic leading-none tracking-tighter italic leading-none italic shadow-sm leading-none">Immersion Liquid Cooling</span>
                                <span class="text-[10px] text-indigo-400 uppercase font-black tracking-widest block mt-3 italic tracking-tighter leading-none shadow-sm leading-none">Fluorinert Dynamic Circulation active</span>
                            </div>
                        </div>
                        <div class="w-4 h-4 rounded-full relative z-10 shadow-inner overflow-hidden flex items-center justify-center" :class="liquidCooling ? 'bg-indigo-500 shadow-[0_0_15px_#6366f1]' : 'bg-slate-700'">
                            <div class="w-2 h-2 bg-white/20 rounded-full animate-ping" x-show="liquidCooling"></div>
                        </div>
                    </button>
                    
                    <div class="grid grid-cols-2 gap-5 leading-none px-1">
                        <div class="space-y-4 leading-none">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pl-2 italic">Energy Source</span>
                            <div class="flex bg-white/5 rounded-2xl p-1.5 border border-white/10 shadow-inner">
                                <button @click="renewableEnergy = 100" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase transition-all" :class="renewableEnergy === 100 ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500'">Solar/Wind</button>
                                <button @click="renewableEnergy = 40" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase transition-all" :class="renewableEnergy === 40 ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500'">Grid Hyp.</button>
                            </div>
                        </div>
                        <div class="space-y-4 leading-none text-right">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none pr-2 italic">Compute OS</span>
                            <div class="flex bg-white/5 rounded-2xl p-1.5 border border-white/10 shadow-inner">
                                <button class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase bg-white text-slate-900 shadow-xl italic tracking-tighter">Cluster-OS</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-16 p-12 bg-black rounded-[3.5rem] border border-white/10 shadow-3xl group/confirm overflow-hidden relative">
                <!-- Action Glow -->
                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent top-0 animate-[pulse_2s_infinite]"></div>
                
                <div class="flex items-center justify-between mb-10 relative z-10 leading-none">
                    <div>
                        <span class="text-[12px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-4 leading-none italic tracking-tighter leading-none italic shadow-sm italic shadow-sm leading-none italic">Neural Cluster Deployment Capex</span>
                        <span class="text-6xl font-black text-indigo-500 italic tracking-tighter italic shadow-sm leading-none" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-10">
                    <button class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black py-7 rounded-2xl transition-all shadow-[0_30px_60px_rgba(99,102,241,0.4)] uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-[0.98] group/btnprimary relative overflow-hidden">
                        <span class="relative z-10">Commission Strategic Cluster</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover/btnprimary:translate-x-1.5 transition-transform relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-[1500ms]"></div>
                    </button>
                    <p class="text-center text-[9px] text-slate-500 uppercase font-black italic tracking-widest mt-2 leading-none">Awaiting HPC Partition Allocation (v.2026. Cluster-OS)</p>
                </div>
            </div>
        </div>
    </div>
</div>
