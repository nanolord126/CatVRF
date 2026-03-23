@php
    declare(strict_types=1);
@endphp

<div x-data="{
    pierLength: 20,
    platformWidth: 8,
    underwaterLighting: true,
    helipad: false,
    smartDocking: true,
    
    get totalArea() {
        return (this.pierLength * 3) + (this.platformWidth * 6);
    },
    
    get estimatedCost() {
        let base = this.totalArea * 120000;
        if (this.underwaterLighting) base += 450000;
        if (this.helipad) base += 2500000;
        if (this.smartDocking) base += 850000;
        return base;
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[700px]">
        
        <!-- Visual: Pier/Docking Simulation -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5">
            <!-- HUD -->
            <div class="absolute top-8 left-8 z-20">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-md px-4 py-2 rounded-full border border-blue-500/30">
                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest italic">Dock-Core v.2026 Admin</span>
                </div>
            </div>

            <!-- Visualization -->
            <div class="flex-grow flex items-center justify-center p-12 relative overflow-hidden">
                <!-- Water Caustics -->
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(59,130,246,0.1)_0%,transparent_70%)] animate-pulse"></div>
                
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-[0_0_50px_rgba(59,130,246,0.2)]" preserveAspectRatio="xMidYMid meet">
                    <!-- Pier Structure -->
                    <path :d="`M 200 280 L 200 ${280 - pierLength*6} L ${200 - platformWidth*4} ${280 - pierLength*6 - 20} L ${200 + platformWidth*4} ${280 - pierLength*6 - 20} Z`" 
                          fill="rgba(59,130,246,0.05)" stroke="rgba(59,130,246,0.4)" stroke-width="2" class="transition-all duration-700" />
                    
                    <!-- Pier Piles (Verticals) -->
                    <template x-for="p in 4">
                        <line :x1="180 + (p*15)" y1="280" :x2="180 + (p*15)" :y2="280 - pierLength*6" stroke="rgba(59,130,246,0.2)" stroke-width="1" />
                    </template>

                    <!-- Helipad Visual -->
                    <g x-show="helipad" class="transition-all">
                        <circle :cx="200" :cy="280 - pierLength*6 - 20" r="15" fill="none" stroke="#3b82f6" stroke-width="2" stroke-dasharray="4 4" />
                        <text :x="195" :y="280 - pierLength*6 - 15" fill="#3b82f6" font-size="12" font-weight="black">H</text>
                    </g>

                    <!-- Smart Docking Beacons -->
                    <g x-show="smartDocking">
                        <circle cx="160" cy="240" r="2" fill="#3b82f6" class="animate-ping" />
                        <circle cx="240" cy="240" r="2" fill="#3b82f6" class="animate-ping" />
                    </g>

                    <!-- Underwater Lighting Effects -->
                    <g x-show="underwaterLighting">
                        <defs>
                            <radialGradient id="lightGlow">
                                <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4" />
                                <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
                            </radialGradient>
                        </defs>
                        <ellipse cx="200" cy="270" rx="60" ry="20" fill="url(#lightGlow)" />
                    </g>
                </svg>
            </div>

            <!-- Stats Overlay -->
            <div class="p-8 grid grid-cols-3 gap-4 relative z-10">
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">Total Area</span>
                    <span class="text-xl text-white font-black italic" x-text="totalArea + ' m²'"></span>
                </div>
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">Load Capacity</span>
                    <span class="text-xl text-white font-black italic">600 t/m²</span>
                </div>
                <div class="bg-black/40 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-tighter mb-1 leading-none">Safety-OS</span>
                    <span class="text-xl text-white font-black italic">SIL-3</span>
                </div>
            </div>
        </div>

        <!-- Controls: Infrastructure Design -->
        <div class="bg-slate-900/50 p-8 lg:p-12 flex flex-col rounded-[2.5rem] border border-white/5">
            <div class="mb-10">
                <h3 class="text-3xl font-black text-white italic tracking-tighter uppercase leading-none">Smart Private Pier</h3>
                <p class="text-[10px] text-blue-500 font-extrabold uppercase tracking-[0.2em] mt-3 opacity-60 italic">Hydro-Mechanical Waterfront Infrastructure</p>
            </div>

            <div class="flex-grow space-y-8">
                <!-- Dimensions -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex justify-between items-end">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Pier Length (M)</span>
                            <span class="text-lg font-black text-white italic leading-none" x-text="pierLength"></span>
                        </div>
                        <input type="range" x-model="pierLength" min="5" max="40" step="1" 
                               class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-blue-500">
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-end border-l border-white/5 pl-6">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Platform Width (M)</span>
                            <span class="text-lg font-black text-white italic leading-none" x-text="platformWidth"></span>
                        </div>
                        <input type="range" x-model="platformWidth" min="4" max="15" step="1" 
                               class="w-full h-1.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-blue-500">
                    </div>
                </div>

                <!-- Systems Integration -->
                <div class="space-y-4">
                    <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-1 leading-none">Hydro-OS Deployment</span>
                    
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Underwater Bi-Optics</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">RGB-W 1200 Lumens / Sea-Grade</p>
                        </div>
                        <button @click="underwaterLighting = !underwaterLighting" class="w-12 h-6 rounded-full relative transition-all shadow-inner" :class="underwaterLighting ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="underwaterLighting ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Helipad Module</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Reinforced Deck for Airbus H145</p>
                        </div>
                        <button @click="helipad = !helipad" class="w-12 h-6 rounded-full relative transition-all shadow-inner" :class="helipad ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="helipad ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Smart Dock AI</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Autonomous Positioning Control</p>
                        </div>
                        <button @click="smartDocking = !smartDocking" class="w-12 h-6 rounded-full relative transition-all shadow-inner" :class="smartDocking ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="smartDocking ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Totalization -->
            <div class="mt-10 pt-8 border-t border-white/5">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-black block tracking-[0.2em] mb-1">Contractor Estimate</span>
                        <span class="text-5xl font-black text-white italic tracking-tighter" x-text="formatPrice(estimatedCost)"></span>
                    </div>
                </div>

                <button class="w-full bg-blue-500 hover:bg-blue-400 text-slate-950 font-black py-6 rounded-3xl shadow-[0_20px_40px_rgba(59,130,246,0.3)] transition-all flex items-center justify-center space-x-4 group overflow-hidden relative active:scale-95">
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <span class="text-xl italic uppercase tracking-tighter relative z-10">Export Engineering Blueprint</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
