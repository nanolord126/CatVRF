@php
    declare(strict_types=1);
@endphp

<div x-data="{
    type: 'dome',
    width: 6,
    length: 10,
    height: 3.5,
    ventilation: true,
    misting: false,
    ledGrow: true,
    
    get volume() {
        let base = this.width * this.length * this.height;
        return this.type === 'dome' ? Math.round(base * 0.7) : Math.round(base * 0.85);
    },
    
    get totalPrice() {
        let basePrice = this.volume * 4500;
        if (this.ventilation) basePrice += 45000;
        if (this.misting) basePrice += 32000;
        if (this.ledGrow) basePrice += 85000;
        return basePrice;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[700px]">
        
        <!-- Visual Section -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5">
            <!-- Header Label -->
            <div class="absolute top-8 left-8 z-20">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-md px-4 py-2 rounded-full border border-emerald-500/30">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] text-emerald-400 font-black uppercase tracking-widest italic">Phyto-Core v.2026 Engine</span>
                </div>
            </div>

            <!-- Dynamic SVG Rendering -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden">
                <!-- Background Pulse -->
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(16,185,129,0.1)_0%,transparent_70%)] animate-pulse"></div>
                
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-[0_0_50px_rgba(16,185,129,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Ground Plate -->
                    <ellipse cx="200" cy="240" rx="140" ry="40" fill="rgba(16,185,129,0.05)" />
                    
                    <!-- Geodesic Dome -->
                    <g x-show="type === 'dome'">
                        <path :d="`M ${200 - width*15} 240 Q 200 ${240 - height*30} ${200 + width*15} 240`" 
                              fill="none" stroke="url(#greenGrad)" stroke-width="2" class="transition-all duration-700" />
                        <path :d="`M ${200 - width*15} 240 a ${width*15} ${height*15} 0 0 1 ${width*30} 0`" 
                              fill="rgba(16,185,129,0.1)" stroke="rgba(16,185,129,0.3)" stroke-width="1" class="transition-all duration-700" />
                        <!-- Grid Lines -->
                        <template x-for="i in 5">
                            <path :d="`M ${200 - width*15 + i*width*6} 240 L 200 ${240 - height*15}`" 
                                  stroke="rgba(16,185,129,0.1)" stroke-width="0.5" />
                        </template>
                    </g>
                    
                    <!-- Tunnel View -->
                    <g x-show="type === 'tunnel'">
                        <rect :x="200 - width*15" :y="240 - height*15" :width="width*30" :height="height*15" 
                              rx="10" fill="none" stroke="rgba(16,185,129,0.5)" stroke-width="2" />
                        <path :d="`M ${200 - width*15} ${240 - height*10} L ${200 + width*15} ${240 - height*10}`" 
                              stroke="rgba(16,185,129,0.2)" stroke-dasharray="4 4" />
                    </g>

                    <!-- Functional Elements -->
                    <g x-show="misting" class="animate-pulse">
                        <circle cx="200" cy="180" r="2" fill="#10b981" />
                        <circle cx="170" cy="190" r="2" fill="#10b981" />
                        <circle cx="230" cy="190" r="2" fill="#10b981" />
                        <path d="M 180 180 q 20 20 40 0" fill="none" stroke="#10b981" stroke-width="0.5" stroke-dasharray="2 2" />
                    </g>

                    <!-- Definitions -->
                    <defs>
                        <linearGradient id="greenGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#065f46;stop-opacity:1" />
                            <stop offset="50%" style="stop-color:#10b981;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#065f46;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            <!-- Stats Overlay -->
            <div class="p-8 grid grid-cols-3 gap-4 relative z-10">
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">Total Volume</span>
                    <span class="text-xl text-white font-black italic" x-text="volume + ' m³'"></span>
                </div>
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">Photo Yield</span>
                    <span class="text-xl text-white font-black italic">88% Opt.</span>
                </div>
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">OS Load</span>
                    <span class="text-xl text-white font-black italic">14% AI</span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/50 p-8 lg:p-12 flex flex-col rounded-[2.5rem] border border-white/5">
            <div class="mb-10">
                <h3 class="text-3xl font-black text-white italic tracking-tighter uppercase leading-none">Greenhouse Phyto-Lab</h3>
                <p class="text-[10px] text-emerald-500 font-extrabold uppercase tracking-[0.2em] mt-3 opacity-60 italic">Automated Smart Climate Engineering</p>
            </div>

            <div class="flex-grow space-y-8">
                <!-- Geometry Type -->
                <div class="space-y-4">
                    <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Structure Geometry</span>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="type = 'dome'" 
                                :class="type === 'dome' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10'"
                                class="py-4 rounded-2xl border font-black italic uppercase text-xs transition-all tracking-tighter">Geodesic Dome</button>
                        <button @click="type = 'tunnel'" 
                                :class="type === 'tunnel' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10'"
                                class="py-4 rounded-2xl border font-black italic uppercase text-xs transition-all tracking-tighter">Industrial Tunnel</button>
                    </div>
                </div>

                <!-- Dimensions -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex justify-between items-end">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Width (M)</span>
                            <span class="text-lg font-black text-white italic leading-none" x-text="width"></span>
                        </div>
                        <input type="range" x-model="width" min="3" max="15" step="1" 
                               class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-end border-l border-white/5 pl-6">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Height (M)</span>
                            <span class="text-lg font-black text-white italic leading-none" x-text="height"></span>
                        </div>
                        <input type="range" x-model="height" min="2" max="6" step="0.5" 
                               class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                    </div>
                </div>

                <!-- Automation Modules -->
                <div class="space-y-4">
                    <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Phyto-OS Integration</span>
                    
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Misting System</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">High-pressure fogging 60 BAR</p>
                        </div>
                        <button @click="misting = !misting" class="w-12 h-6 rounded-full relative transition-all shadow-inner" :class="misting ? 'bg-emerald-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="misting ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Quantum LED Grow</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Full Spectrum 660nm + 3000K</p>
                        </div>
                        <button @click="ledGrow = !ledGrow" class="w-12 h-6 rounded-full relative transition-all shadow-inner" :class="ledGrow ? 'bg-emerald-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="ledGrow ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price & Action -->
            <div class="mt-10 pt-8 border-t border-white/5">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-1">Estimated Cost</span>
                        <span class="text-5xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>

                <button class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-6 rounded-3xl shadow-[0_20px_40px_rgba(16,185,129,0.3)] transition-all flex items-center justify-center space-x-4 group overflow-hidden relative active:scale-95">
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <span class="text-xl italic uppercase tracking-tighter relative z-10">Generate Phyto-Core Project</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
