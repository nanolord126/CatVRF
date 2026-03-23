@php
    declare(strict_types=1);
    
    /** @var \App\Domains\ConstructionMaterials\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
    $correlationId = (string) \Illuminate\Support\Str::uuid();
@endphp

<div x-data="{
    area: 500,
    treeCount: 20,
    style: 'modern',
    autoWatering: true,
    outdoorLighting: true,
    drainage: false,
    showTelemetry: true,
    
    get plantDensity() {
        return Math.round((this.treeCount / this.area) * 100);
    },
    
    get ecoScore() {
        let score = 65;
        if (this.autoWatering) score += 15;
        if (this.drainage) score += 10;
        return Math.min(score, 100);
    },
    
    get totalPrice() {
        let basePrice = this.area * 3500;
        basePrice += this.treeCount * 12000;
        
        if (this.style === 'english') basePrice *= 1.3;
        if (this.autoWatering) basePrice += 145000;
        if (this.outdoorLighting) basePrice += 85000;
        if (this.drainage) basePrice += 220000;
        
        return basePrice;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(132, 204, 22, 0.2) 1px, transparent 0); background-size: 50px 50px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[900px] relative z-10 font-sans uppercase">
        
        <!-- Visual Section: Bio-Core Hub -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner transition-all duration-700">
            
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-10">
                <div class="absolute inset-inline-0 h-[3px] bg-lime-400/50 blur-md animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-10 left-10 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-4 bg-black/60 backdrop-blur-2xl px-6 py-2.5 rounded-xl border-l-4 border-lime-500 shadow-lg">
                    <div class="w-2.5 h-2.5 bg-lime-500 rounded-full animate-pulse shadow-[0_0_12px_rgba(132,204,22,0.8)]"></div>
                    <span class="text-[10px] text-lime-400 font-black uppercase tracking-[0.3em] font-black italic italic leading-none">Bio-Core / v.26.L</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-4 py-1.5 rounded-lg text-[8px] text-lime-600 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-lime-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Technical Telemetry HUD -->
            <div class="absolute top-10 right-10 z-20 flex flex-col space-y-4 text-right italic font-black">
                <div x-show="showTelemetry" class="space-y-4 font-black italic">
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-lime-800/30">
                        <span class="text-[8px] text-lime-600 font-black uppercase tracking-widest block mb-1 font-black leading-none">Soil PH</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none" x-text="'6.8 OK'"></span>
                    </div>
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-lime-800/30 font-black italic">
                        <span class="text-[8px] text-lime-600 font-black uppercase tracking-widest block mb-1 font-black leading-none">Eco Score</span>
                        <span class="text-2xl text-white tracking-tighter uppercase leading-none text-lime-400 font-black" x-text="ecoScore + ' PTS'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization: Bio-Core Plan -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(132,204,22,0.1)_0%,transparent_60%)] animate-pulse"></div>
                
                <svg viewBox="0 0 500 500" class="w-full h-full drop-shadow-[0_0_80px_rgba(132,204,22,0.25)] transition-all duration-1000 filter brightness-110" preserveAspectRatio="xMidYMid meet">
                    <!-- Bio Grayscale Grid -->
                    <defs>
                        <pattern id="bioGrid" width="50" height="50" patternUnits="userSpaceOnUse">
                            <path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(132,204,22,0.1)" stroke-width="0.5" />
                        </pattern>
                        <linearGradient id="leafGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3f6212;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#84cc16;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    
                    <rect x="0" y="0" width="500" height="500" fill="url(#bioGrid)" />

                    <!-- Site Boundaries -->
                    <rect x="50" y="50" width="400" height="400" fill="rgba(132,204,22,0.05)" stroke="#4d7c0f" stroke-width="2" rx="10" />
                    
                    <!-- Dynamic Plantings -->
                    <g>
                        <template x-for="i in Array.from({length: Math.min(treeCount, 40)})">
                            <circle :cx="70 + (Math.sin(i * 123.45) * 0.5 + 0.5) * 360" 
                                    :cy="70 + (Math.cos(i * 543.21) * 0.5 + 0.5) * 360" 
                                    r="8" fill="url(#leafGrad)" class="transition-all duration-1000 opacity-60">
                                <animate attributeName="r" values="7;9;7" dur="4s" repeatCount="indefinite" />
                            </circle>
                        </template>
                    </g>

                    <!-- Irrigation Hub Mapping -->
                    <template x-if="autoWatering">
                        <circle cx="250" cy="250" r="180" fill="none" stroke="#84cc16" stroke-width="0.5" stroke-dasharray="8,8" opacity="0.4">
                            <animate attributeName="stroke-dashoffset" from="100" to="0" dur="10s" repeatCount="indefinite" />
                        </circle>
                    </template>

                    <!-- Terrain Geometry Path -->
                    <path d="M 50 300 Q 250 150 450 300" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="60" stroke-linecap="round" />
                </svg>
            </div>

            <!-- Bio Systems HUD Footer -->
            <div class="p-10 grid grid-cols-3 gap-1 relative z-10 bg-black/50 backdrop-blur-2xl border-t border-white/5 italic font-black uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-lime-500/40 font-black text-lime-500">
                    <span class="text-[8px] text-lime-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Bio Mass</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none font-black font-black font-black uppercase underline leading-none uppercase leading-none uppercase leading-none font-black uppercase font-black italic leading-none uppercase leading-none font-black font-black uppercase" x-text="plantDensity + '% DENSE'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-lime-500/40 font-black">
                    <span class="text-[8px] text-lime-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Hydration Hub</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none transition-colors" :class="autoWatering ? 'text-lime-400 font-black underline' : 'text-slate-600'" x-text="autoWatering ? 'AUTO' : 'MANUAL'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-lime-500/40 font-black">
                    <span class="text-[8px] text-lime-600 uppercase font-black block tracking-widest mb-1 italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic font-black uppercase font-black">Unit Load</span>
                    <span class="text-3xl text-white tracking-tighter uppercase leading-none font-black italic" x-text="Math.floor(totalPrice / 5000000 * 100) + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Bio Architecture -->
        <div class="bg-lime-950/10 p-12 lg:p-16 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden backdrop-blur-3xl italic font-black">
            
            <div class="mb-16 relative group/header font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                <div class="flex items-center justify-between font-black uppercase tracking-tighter italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">
                    <div>
                        <h3 class="text-5xl font-black text-white tracking-ti g hter uppercase leading-none group-hover/header:text-lime-400 transition-colors font-black flex items-center space-x-4">
                            <span>Bio-Core</span>
                        </h3>
                        <p class="text-[10px] text-lime-600 font-extrabold uppercase tracking-[0.5em] mt-4 opacity-80 italic flex items-center space-x-3">
                             <span class="w-12 h-[1px] bg-lime-500 animate-pulse"></span>
                             <span>Industrial Landscape Tuner</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-12 overflow-y-auto pr-4 custom-scrollbar font-black uppercase leading-none font-black bg-black/5 p-4 rounded-3xl underline decoration-lime-500/30 font-black italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                
                <!-- Terrain Parameters -->
                <div class="p-8 bg-black/40 rounded-[2.5rem] border border-white/5 relative overflow-hidden group/opt hover:bg-black/60 transition-all font-black uppercase underline-offset-8">
                    <div class="flex justify-between items-center mb-8 pl-2 font-black italic leading-none">
                        <span class="text-[11px] text-lime-600 font-black uppercase tracking-[0.3em] font-black italic underline leading-none uppercase tracking-[0.2em] font-black italic leading-none font-black leading-none">01. Terrain Geometry</span>
                        <div class="w-16 h-[1px] bg-lime-500/30"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 font-black uppercase tracking-tighter italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">
                        <div class="space-y-6">
                            <div class="flex justify-between items-end mb-4 font-black">
                                <span class="text-[10px] text-lime-600 uppercase font-black tracking-widest font-black italic">Total Area (M²)</span>
                                <span class="text-3xl text-white tracking-tighter font-black" x-text="area + ' M²'"></span>
                            </div>
                            <input type="range" x-model="area" min="50" max="5000" step="50" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-lime-600">
                        </div>
                        <div class="space-y-6">
                            <div class="flex justify-between items-end mb-4 font-black">
                                <span class="text-[10px] text-lime-600 uppercase font-black tracking-widest font-black italics">Bio Units</span>
                                <span class="text-3xl text-white tracking-tighter font-black italic font-black" x-text="treeCount"></span>
                            </div>
                            <input type="range" x-model="treeCount" min="5" max="250" step="5" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-lime-600">
                        </div>
                    </div>
                </div>

                <!-- Architectural Style -->
                <div class="grid grid-cols-2 gap-4 font-black uppercase">
                    <template x-for="s in ['modern', 'english']">
                        <button @click="style = s" 
                                :class="style === s ? 'bg-lime-700/30 text-white border-lime-500 shadow-xl shadow-lime-900/50 underline' : 'bg-black/40 text-slate-500 border-white/5 font-black italic leading-none uppercase leading-none'"
                                class="p-4 rounded-3xl border transition-all text-xs font-black uppercase tracking-widest text-center" x-text="s === 'modern' ? 'Modern Zen' : 'English Park'">
                        </button>
                    </template>
                </div>

                <!-- Landscape Systems Toggle -->
                <div class="space-y-6 font-black uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none font-black">
                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-lime-500/30 group/sys font-black uppercase">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none">
                            <div class="w-14 h-14 bg-lime-500/10 rounded-2xl flex items-center justify-center border border-lime-500/20 group-hover/sys:bg-lime-500/20 transition-all font-black uppercase">
                                <svg class="w-7 h-7 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter font-black font-black uppercase leading-none">Smart Irrigation HUB</span>
                                <p class="text-[9px] text-lime-600 tracking-[.2em] mt-2 whitespace-nowrap font-black font-black italic leading-none uppercase underline leading-none uppercase font-black leading-none italic font-black font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none font-black">Rain Bird Quantum Sensor</p>
                            </div>
                        </div>
                        <button @click="autoWatering = !autoWatering" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner font-black uppercase" 
                                :class="autoWatering ? 'bg-lime-600 shadow-[0_0_20px_rgba(132,204,22,0.5)]' : 'bg-slate-800 font-black italic leading-none uppercase leading-none font-black italic leading-none font-black underline decoration-lime-500/30 font-black italic leading-none uppercase'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                                 :style="autoWatering ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-8 bg-black/40 rounded-[2.5rem] border border-white/5 transition-all hover:border-lime-500/30 group/sys font-black uppercase flex items-center space-x-2">
                        <div class="flex space-x-6 items-center font-black uppercase leading-none">
                            <div class="w-14 h-14 bg-lime-500/10 rounded-2xl flex items-center justify-center border border-lime-500/20 group-hover/sys:bg-lime-500/20 transition-all font-black uppercase inline-flex items-center space-x-2">
                                <svg class="w-7 h-7 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-lg font-black italic block leading-none tracking-tighter font-black font-black uppercase leading-none">Terrain Ledger</span>
                                <p class="text-[9px] text-lime-600 tracking-[.2em] mt-2 whitespace-nowrap font-black font-black italic leading-none uppercase underline leading-none uppercase font-black leading-none italic font-black font-black italic leading-none uppercase underline leading-none uppercase leading-none uppercase leading-none font-black">Site-Level Drainage Control</p>
                            </div>
                        </div>
                        <button @click="drainage = !drainage" 
                                class="w-16 h-8 rounded-full relative transition-all duration-500 shadow-inner" 
                                :class="drainage ? 'bg-lime-600 shadow-[0_0_20px_rgba(132,204,22,0.5)]' : 'bg-slate-800 font-black italic font-black'">
                            <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all duration-500 shadow-xl" 
                                 :style="drainage ? 'inset-inline-start: 36px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Valuation HUD -->
            <div class="mt-16 p-12 bg-lime-600 rounded-[4rem] shadow-[0_40px_80px_rgba(0,0,0,0.5)] relative overflow-hidden group/total font-black uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.2)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_5s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-10 font-black italic leading-none uppercase leading-none font-black leading-none italic font-black uppercase">
                    <div>
                        <span class="text-[11px] font-black text-slate-950 uppercase tracking-[0.5em] block mb-3 font-black underline italic">Bio-Capital Configuration</span>
                        <div class="text-6xl font-black text-slate-950 italic tracking-tighter bg-black/10 px-8 py-3 rounded-2xl shadow-inner font-black underline italic transition-all duration-300" x-text="formatPrice(totalPrice)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-16 py-7 rounded-[2.5rem] font-black uppercase text-sm tracking-[0.4em] shadow-2xl hover:bg-slate-800 transition-all hover:scale-105 active:scale-95 italic font-black underline leading-none font-black uppercase underline leading-none font-black italic leading-none uppercase leading-none font-black font-black uppercase">
                        Lock Bio-Logic
                    </button>
                </div>
            </div>
            
            <p class="mt-10 text-[9px] text-lime-800 italic uppercase tracking-[0.6em] text-center leading-relaxed font-black underline uppercase whitespace-nowrap leading-none transition-all duration-300">
                Precision Landscape Engineering. Biological Integrity Verified. Bio-Core v.26.L Standard.
            </p>
        </div>
    </div>
</div>
