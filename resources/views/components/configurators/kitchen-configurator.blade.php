@php
    /** @var \App\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
    $tenantId = tenant()->id ?? 'default';
@endphp

@props(['template'])

<div x-data="kitchenConfigurator(@js($template))" 
     class="relative group p-8 bg-slate-950/60 backdrop-blur-2xl rounded-3xl border border-teal-500/20 shadow-[0_0_50px_-12px_rgba(20,184,166,0.3)] overflow-hidden transition-all duration-500 animate-in fade-in zoom-in-95"
     data-correlation-id="{{ $correlationId }}"
     data-tenant-id="{{ $tenantId }}">
    
    <!-- GRID HUD OVERLAY -->
    <div class="absolute inset-0 pointer-events-none opacity-20" 
         style="background-image: radial-gradient(#14b8a6 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
    
    <!-- TECH SCANLINE -->
    <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-teal-500/50 to-transparent top-0 animate-scanline"></div>

    <div class="relative flex flex-col lg:flex-row gap-10">
        
        <!-- LEFT PANEL: KITCHEN CORE VISUALIZER -->
        <div class="inline-size-full lg:inline-size-2/3 bg-black/40 rounded-2xl p-6 min-block-size-[500px] flex items-center justify-center relative border border-white/5 ring-1 ring-white/10 overflow-hidden group/canvas">
            
            <!-- STATUS CHIPS -->
            <div class="absolute inset-block-start-6 inset-inline-start-6 z-20 flex flex-col gap-3">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur-md px-4 py-1.5 rounded-full border border-teal-500/30 text-[10px] text-teal-400 uppercase font-black animate-pulse">
                    <span class="block-size-1.5 inline-size-1.5 rounded-full bg-teal-500 shadow-[0_0_8px_#14b8a6]"></span>
                    Kitchen-Core Engine v.2026
                </div>
                <div class="bg-indigo-500/10 backdrop-blur-md px-3 py-1 rounded-md border border-indigo-500/20 text-[9px] text-indigo-300 font-mono tracking-tighter">
                    CORRELATION_ID: {{ substr($correlationId, 0, 8) }}
                </div>
            </div>

            <div class="absolute inset-block-end-6 inset-inline-end-6 z-20 flex flex-col items-end gap-1 font-mono text-[10px] text-slate-500 uppercase">
                <div x-text="'LAYOUT: ' + config.layout"></div>
                <div x-text="'DIM_A: ' + config.widthA + 'mm'"></div>
                <div x-text="'MODULES: ' + selectedModules.length"></div>
            </div>
            
            <!-- SVG VIEWPORT -->
            <svg viewBox="0 0 800 400" 
                 class="inline-size-full block-size-full max-block-size-[500px] drop-shadow-[0_0_30px_rgba(20,184,166,0.1)]" 
                 preserveAspectRatio="xMidYMid meet">
                
                <defs>
                    <linearGradient id="moduleGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:rgba(255,255,255,0.05);stop-opacity:1" />
                        <stop offset="100%" style="stop-color:rgba(255,255,255,0.01);stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <!-- FLOOR / WALL INDICATION -->
                <rect x="20" y="360" width="760" height="2" fill="url(#moduleGradient)" class="opacity-30" />
                <path d="M50 360 L50 40 L750 40" fill="none" class="stroke-teal-500/10 stroke-[1px]" stroke-dasharray="4 4" />

                <!-- MODULE RENDERING -->
                <template x-for="(module, index) in selectedModules" :key="index">
                    <g :class="{'animate-in fade-in zoom-in-95 duration-500': true}">
                        <!-- Module Body -->
                        <rect 
                            :x="module.x" 
                            :y="module.y" 
                            :width="module.width" 
                            :height="module.height" 
                            :fill="config.colors[module.type] || '#1e293b'" 
                            class="stroke-white/20 stroke-1 transition-all duration-700 hover:stroke-teal-400 hover:filter-[url(#glow)]"
                        />
                        <!-- Handle / Tech Detail -->
                        <rect 
                            :x="module.x + 5" 
                            :y="module.y + 10" 
                            width="2" 
                            :height="module.height - 20" 
                            fill="rgba(20,184,166,0.3)" 
                        />
                    </g>
                </template>
                
                <!-- Countertop -->
                <rect x="45" y="340" width="710" height="12" 
                      :fill="config.worktop === 'quartz' ? '#cbd5e1' : (config.worktop === 'acryl' ? '#94a3b8' : '#475569')" 
                      class="stroke-white/10 transition-colors duration-500" />
            </svg>
        </div>

        <!-- RIGHT PANEL: INDUSTRIAL STEP WIZARD -->
        <div class="inline-size-full lg:inline-size-1/3 flex flex-col space-y-8">
            
            <div class="space-y-2">
                <h2 class="text-3xl font-black text-white tracking-widest uppercase italic leading-none">
                    Kitchen<span class="text-teal-500">_Core</span>
                </h2>
                <div class="h-1 inline-size-12 bg-teal-500 rounded-full"></div>
            </div>

            <!-- STEPPER PROGRESS -->
            <div class="flex justify-between items-center px-1 gap-2">
                <template x-for="n in 5">
                    <div class="flex-1 h-1 rounded-full transition-all duration-700"
                        :class="step >= n ? 'bg-teal-500 shadow-[0_0_15px_#14b8a6]' : 'bg-white/5'">
                    </div>
                </template>
            </div>

            <!-- WIZARD INTERFACE -->
            <div class="flex-grow overflow-y-auto max-block-size-[450px] pr-4 custom-scrollbar space-y-6">
                
                <!-- STEP 1: SPATIAL MAPPING -->
                <div x-show="step === 1" x-transition.opacity.duration.500ms class="space-y-6">
                    <div class="border-inline-start-4 border-teal-500 pl-4 py-1">
                        <span class="text-[10px] text-teal-500 font-black uppercase tracking-[0.2em]">Phase 01</span>
                        <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter">Spatial Mapping</h3>
                    </div>
                    
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-widest flex justify-between">
                                Layout Topology
                                <span class="text-teal-400 font-mono" x-text="config.layout"></span>
                            </label>
                            <select x-model="config.layout" class="inline-size-full bg-black/60 border border-white/10 rounded-xl text-white font-bold italic py-3 focus:border-teal-500 focus:ring-1 focus:ring-teal-500/30 transition-all outline-none">
                                <option value="linear">Line-Core (Linear)</option>
                                <option value="l_shape">Corner-Core (L-Shape)</option>
                                <option value="u_shape">U-Core (U-Shape)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] text-slate-500 font-black uppercase">Primary Wall (mm)</label>
                                <input type="number" x-model.number="config.widthA" class="inline-size-full bg-black/60 border border-white/10 text-white font-mono py-3 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-all outline-none px-4">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] text-slate-500 font-black uppercase">Vertical Span (mm)</label>
                                <input type="number" x-model.number="config.heightTotal" class="inline-size-full bg-black/60 border border-white/10 text-white font-mono py-3 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-all outline-none px-4">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: LOWER MODULE INJECTION -->
                <div x-show="step === 2" x-transition.opacity.duration.500ms class="space-y-6">
                    <div class="border-inline-start-4 border-teal-500 pl-4 py-1">
                        <span class="text-[10px] text-teal-500 font-black uppercase tracking-[0.2em]">Phase 02</span>
                        <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter">Base Tier Assembly</h3>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="moduleType in moduleLibrary.bottom">
                            <button @click="addModule('bottom', moduleType)" 
                                    class="group/item relative p-4 bg-white/5 border border-white/10 rounded-2xl hover:border-teal-500/50 hover:bg-teal-500/5 transition-all text-start overflow-hidden">
                                <div class="absolute inset-block-start-0 inset-inline-end-0 p-2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                    <svg class="inline-size-4 block-size-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <div class="text-[10px] text-teal-500 font-black uppercase" x-text="moduleType.sku"></div>
                                <div class="text-xs text-white font-bold truncate" x-text="moduleType.name"></div>
                                <div class="text-[10px] text-slate-400 font-mono mt-1" x-text="formatPrice(moduleType.price)"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- STEP 3: UPPER MODULE INJECTION -->
                <div x-show="step === 3" x-transition.opacity.duration.500ms class="space-y-6">
                    <div class="border-inline-start-4 border-teal-500 pl-4 py-1">
                        <span class="text-[10px] text-teal-500 font-black uppercase tracking-[0.2em]">Phase 03</span>
                        <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter">Upper Wall Tier</h3>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="moduleType in moduleLibrary.top">
                            <button @click="addModule('top', moduleType)" 
                                    class="group/item relative p-4 bg-white/5 border border-white/10 rounded-2xl hover:border-teal-500/50 hover:bg-teal-500/5 transition-all text-start overflow-hidden">
                                <div class="absolute inset-block-start-0 inset-inline-end-0 p-2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                    <svg class="inline-size-4 block-size-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <div class="text-[10px] text-teal-500 font-black uppercase" x-text="moduleType.sku"></div>
                                <div class="text-xs text-white font-bold truncate" x-text="moduleType.name"></div>
                                <div class="text-[10px] text-slate-400 font-mono mt-1" x-text="formatPrice(moduleType.price)"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- STEP 4: MATERIAL SPECIFICATION -->
                <div x-show="step === 4" x-transition.opacity.duration.500ms class="space-y-6">
                    <div class="border-inline-start-4 border-teal-500 pl-4 py-1">
                        <span class="text-[10px] text-teal-500 font-black uppercase tracking-[0.2em]">Phase 04</span>
                        <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter">Material Surface</h3>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-3">
                            <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Facade Finish</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="config.facadeMaterial = 'mdf'" 
                                        :class="config.facadeMaterial === 'mdf' ? 'border-teal-500 bg-teal-500/10 text-teal-400' : 'border-white/10 text-slate-400'" 
                                        class="border py-3 rounded-xl text-[10px] font-black uppercase transition-all">MDF Painted</button>
                                <button @click="config.facadeMaterial = 'ldsp'" 
                                        :class="config.facadeMaterial === 'ldsp' ? 'border-teal-500 bg-teal-500/10 text-teal-400' : 'border-white/10 text-slate-400'" 
                                        class="border py-3 rounded-xl text-[10px] font-black uppercase transition-all">LDSP Texture</button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Countertop Matrix</span>
                            <select x-model="config.worktop" class="inline-size-full bg-black/60 border border-white/10 rounded-xl text-white font-bold italic py-3 outline-none focus:border-teal-500">
                                <option value="plastic">HPL Composite (Eco)</option>
                                <option value="acryl">Acrylic Resin (Standard)</option>
                                <option value="quartz">Quartz Agglomerate (Pro)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: CORE OUTPUT -->
                <div x-show="step === 5" x-transition.opacity.duration.500ms class="space-y-6">
                    <div class="border-inline-start-4 border-emerald-500 pl-4 py-1">
                        <span class="text-[10px] text-emerald-500 font-black uppercase tracking-[0.2em]">Phase 05</span>
                        <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter">System Output</h3>
                    </div>

                    <div class="bg-white/5 rounded-2xl p-6 border border-white/10 space-y-4 font-mono">
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="text-slate-500">ENGINE_STATUS</span>
                            <span class="text-emerald-400 font-bold">READY_FOR_DEPLOY</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400 italic">Module Count:</span>
                            <span class="text-white font-black" x-text="selectedModules.length"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400 italic">Total Mass:</span>
                            <span class="text-white font-black" x-text="formatWeight(totalWeight)"></span>
                        </div>
                        
                        <div class="h-px inline-size-full bg-gradient-to-r from-transparent via-white/10 to-transparent my-4"></div>
                        
                        <div class="flex justify-between items-end">
                            <span class="text-slate-500 text-[10px] font-black uppercase">Final Budget</span>
                            <span class="text-3xl text-emerald-400 font-black drop-shadow-[0_0_15px_rgba(52,211,153,0.4)]" x-text="formatPrice(totalPrice)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GLOBAL CORE ACTIONS -->
            <div class="pt-6 flex gap-4">
                <button x-show="step > 1" 
                        @click="step--" 
                        class="flex-1 py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-white/10 hover:border-white/20 transition-all flex items-center justify-center gap-2 group">
                    <svg class="inline-size-4 block-size-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </button>
                
                <button x-show="step < 5" 
                        @click="nextStep()" 
                        class="flex-1 py-4 bg-teal-600/20 border border-teal-500/50 text-teal-400 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-teal-600/30 hover:shadow-[0_0_20px_rgba(20,184,166,0.2)] transition-all flex items-center justify-center gap-2 group">
                    Execute Next
                    <svg class="inline-size-4 block-size-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                
                <button x-show="step === 5" 
                        @click="saveProject()" 
                        class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-emerald-500 shadow-[0_0_30px_rgba(5,150,105,0.4)] transition-all flex items-center justify-center gap-2 animate-pulse">
                    Deploy Project
                    <svg class="inline-size-4 block-size-4 shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </button>
            </div>

        </div>
    </div>
</div>
