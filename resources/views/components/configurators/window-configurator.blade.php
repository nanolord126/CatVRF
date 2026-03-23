@php
    /** @var \App\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
    $tenantId = tenant()->id ?? 'default';
@endphp

@props(['template'])

<div x-data="windowConfigurator(@js($template))" 
     class="relative group p-8 bg-slate-950/60 backdrop-blur-2xl rounded-3xl border border-blue-500/20 shadow-[0_0_50px_-12px_rgba(59,130,246,0.3)] overflow-hidden transition-all duration-500 animate-in fade-in zoom-in-95"
     data-correlation-id="{{ $correlationId }}"
     data-tenant-id="{{ $tenantId }}">
    
    <!-- GRID HUD OVERLAY -->
    <div class="absolute inset-0 pointer-events-none opacity-20" 
         style="background-image: radial-gradient(#3b82f6 0.5px, transparent 0.5px); background-size: 32px 32px;"></div>
    
    <!-- TECH SCANLINE -->
    <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent top-0 animate-scanline"></div>

    <div class="relative flex flex-col lg:flex-row gap-12">
        
        <!-- LEFT PANEL: OPTICS VISUALIZER -->
        <div class="inline-size-full lg:inline-size-3/5 bg-black/40 rounded-3xl min-block-size-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden group/canvas ring-1 ring-white/10">
            
            <!-- STATUS CHIPS -->
            <div class="absolute inset-block-start-8 inset-inline-start-8 z-20 flex flex-col gap-3 font-mono">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur-md px-4 py-1.5 rounded-full border border-blue-500/30 text-[10px] text-blue-400 uppercase font-black animate-pulse">
                    <span class="block-size-1.5 inline-size-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6]"></span>
                    Optics-Core Engine v.2026
                </div>
                <div class="bg-indigo-500/10 backdrop-blur-md px-3 py-1 rounded-md border border-indigo-500/20 text-[9px] text-indigo-300 font-mono tracking-tighter">
                    CORRELATION_ID: {{ substr($correlationId, 0, 8) }}
                </div>
            </div>

            <div class="absolute inset-block-end-8 inset-inline-end-8 z-20 flex flex-col items-end gap-1 font-mono text-[10px] text-slate-500 uppercase">
                <div x-text="'PROFILE: ' + config.profile"></div>
                <div x-text="'DIM: ' + config.width + 'x' + config.height + 'mm'"></div>
                <div x-text="'K_HEAT: ' + results.uValue"></div>
            </div>

            <!-- SVG Window Rendering -->
            <svg viewBox="0 0 400 600" class="inline-size-full max-block-size-[450px] drop-shadow-[0_0_40px_rgba(59,130,246,0.1)] transition-transform duration-700">
                <defs>
                    <linearGradient id="glass-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#bae6fd;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#0284c7;stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow-optics">
                        <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <!-- Frame -->
                <rect x="40" y="40" width="320" height="520" fill="rgba(248,250,252,0.05)" stroke="#e2e8f0" stroke-width="12" rx="4" class="opacity-80" />
                
                <!-- Panes -->
                <template x-if="config.type === 'single'">
                    <rect x="58" y="58" width="284" height="484" fill="url(#glass-gradient)" opacity="0.4" stroke="#94a3b8" stroke-width="2" />
                </template>

                <template x-if="config.type === 'double'">
                    <g>
                        <rect x="58" y="58" width="140" height="484" fill="url(#glass-gradient)" opacity="0.4" stroke="#94a3b8" />
                        <rect x="202" y="58" width="140" height="484" fill="url(#glass-gradient)" opacity="0.4" stroke="#94a3b8" />
                        <rect x="194" y="46" width="12" height="508" fill="rgba(241,245,249,0.1)" />
                        <rect x="175" y="280" width="15" height="4" fill="#94a3b8" rx="1" />
                        <rect x="175" y="275" width="4" height="15" fill="#94a3b8" rx="1" />
                    </g>
                </template>

                <!-- Dynamic Dimensions -->
                <line x1="40" y1="20" x2="360" y2="20" stroke="#3b82f6" stroke-width="1" stroke-dasharray="4 4" class="opacity-50" />
                <text x="200" y="15" fill="#3b82f6" font-size="12" text-anchor="middle" font-weight="bold" x-text="config.width + ' mm'"></text>

                <line x1="385" y1="40" x2="385" y2="560" stroke="#3b82f6" stroke-width="1" stroke-dasharray="4 4" class="opacity-50" />
                <text x="395" y="300" fill="#3b82f6" font-size="12" text-anchor="middle" font-weight="bold" writing-mode="vertical-rl" x-text="config.height + ' mm'"></text>
            </svg>
        </div>

        <!-- RIGHT PANEL: INDUSTRIAL CONTROLS -->
        <div class="inline-size-full lg:inline-size-2/5 flex flex-col space-y-10">
            <div class="space-y-4">
                <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">
                    Window <span class="text-blue-500 underline decoration-4 underline-offset-8 decoration-blue-500/50">Optics</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-mono uppercase tracking-[0.3em]">Precision Engineering Interface</p>
            </div>

            <div class="space-y-8">
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] text-slate-500 font-black uppercase tracking-widest pl-2 block">Topology Type</label>
                        <select x-model="config.type" class="inline-size-full bg-black/60 border border-white/10 text-white rounded-2xl font-bold italic py-4 px-5 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30 transition-all">
                            <option value="single">Mono-Panel (Static)</option>
                            <option value="double">Dual-Panel (Active)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] text-slate-500 font-black uppercase tracking-widest pl-2 block">Structural Profile</label>
                        <select x-model="config.profile" class="inline-size-full bg-black/60 border border-white/10 text-white rounded-2xl font-bold italic py-4 px-5 outline-none focus:border-blue-500">
                            <option value="rehau_60">REHAU Euro 60</option>
                            <option value="veka_70">VEKA Softline 70</option>
                            <option value="kbe_88">KBE 88 Premium</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 ring-1 ring-white/5 focus-within:ring-blue-500/30 transition-all">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-2 px-1">Width Span (W)</span>
                        <input type="number" x-model.number="config.width" class="bg-transparent border-none text-white text-3xl font-black italic inline-size-full focus:outline-none focus:text-blue-400 transition-all px-1">
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 ring-1 ring-white/5 focus-within:ring-blue-500/30 transition-all">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-2 px-1">Height Span (H)</span>
                        <input type="number" x-model.number="config.height" class="bg-transparent border-none text-white text-3xl font-black italic inline-size-full focus:outline-none focus:text-blue-400 transition-all px-1">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-5 bg-white/5 rounded-2xl border border-white/10 group/toggle cursor-pointer hover:bg-white/10 transition-all" @click="config.mosqNet = !config.mosqNet">
                        <div>
                            <span class="text-white text-sm font-bold italic uppercase tracking-tight">Antivoc Barrier</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mt-1">Fiberglass Mesh System</p>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" :class="config.mosqNet ? 'bg-blue-600 shadow-[0_0_15px_#2563eb]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" :style="config.mosqNet ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-white/5 rounded-2xl border border-white/10 group/toggle cursor-pointer hover:bg-white/10 transition-all" @click="config.microVent = !config.microVent">
                        <div>
                            <span class="text-white text-sm font-bold italic uppercase tracking-tight">Micro-Aero Valve</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mt-1">4-Stage Reducer Lock</p>
                        </div>
                        <div class="inline-size-14 block-size-7 rounded-full relative transition-all duration-300" :class="config.microVent ? 'bg-blue-600 shadow-[0_0_15px_#2563eb]' : 'bg-slate-800'">
                            <div class="absolute inset-block-start-1 block-size-5 inline-size-5 bg-white rounded-full transition-all duration-300 shadow-xl" :style="config.microVent ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CORE BUDGETARY OUTPUT -->
            <div class="bg-blue-600/5 p-8 rounded-3xl border border-blue-500/20 space-y-6 font-mono relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="flex justify-between items-end border-b border-blue-500/10 pb-4">
                    <span class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em]">Total Mass:</span>
                    <span class="text-white font-black italic text-lg shadow-sm" x-text="results.weight + ' KG'"></span>
                </div>
                
                <div class="flex justify-between items-end pt-2">
                    <span class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em]">Procurement Cost:</span>
                    <span class="text-5xl font-black text-white italic tracking-tighter drop-shadow-[0_0_15px_rgba(59,130,246,0.3)]" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveWindow()" class="group relative inline-size-full py-7 bg-white text-slate-950 rounded-3xl font-black uppercase tracking-widest text-xs overflow-hidden transition-all hover:scale-[1.01] active:scale-[0.98] shadow-[0_20px_40px_-15px_rgba(255,255,255,0.2)]">
                <span class="relative z-10 flex items-center justify-center gap-3">
                    Deploy Specifications
                    <svg class="block-size-5 inline-size-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
            </button>
            <p class="text-[9px] text-slate-600 font-black uppercase tracking-widest text-center leading-relaxed opacity-50">
                GOST 30674-99 Compliant | 1.5mm Structural Reinforcement | Roto NX Hardware Logic
            </p>
        </div>
    </div>
</div>
