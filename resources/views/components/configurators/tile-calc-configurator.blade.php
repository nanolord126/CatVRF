@php
    declare(strict_types=1);
    // Привязка к сервису домена ConstructionMaterials
    $materialService = app(\App\Domains\ConstructionMaterials\Services\MaterialService::class);
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    area: 30,
    tileType: 'Ceramic',
    isLarge: false,
    epoxyGrout: true,
    heatedFloor: false,
    correlationId: '{{ Str::uuid() }}',
    
    // Предиктивная аналитика из сервиса (имитация)
    get tileServiceData() {
        return {
            'Ceramic': { matPrice: 1800, labPrice: 1500 },
            'Porcelain': { matPrice: 3500, labPrice: 1800 },
            'Marble': { matPrice: 9500, labPrice: 3500 },
            'Venetian': { matPrice: 14000, labPrice: 3800 }
        }[this.tileType];
    },
    
    get materialPrice() {
        let base = this.tileServiceData.matPrice;
        if (this.isLarge) base *= 1.15;
        return base;
    },
    
    get laborPrice() {
        let base = this.tileServiceData.labPrice;
        if (this.isLarge) base += 800;
        return base;
    },

    get totalPrice() {
        let mat = this.area * this.materialPrice;
        let lab = this.area * this.laborPrice;
        let extras = (this.epoxyGrout ? this.area * 600 : this.area * 150) + (this.heatedFloor ? this.area * 2500 : 0);
        return Math.round(mat + lab + extras);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px]">
        
        <!-- Tile Visual HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5">
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-cyan-500/30">
                    <div class="w-2.5 h-2.5 bg-cyan-500 rounded-full animate-pulse shadow-[0_0_10px_#06b6d4]"></div>
                    <span class="text-[10px] text-cyan-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Grid-Core: ' + tileType + (isLarge ? ' XL' : '')"></span>
                </div>
            </div>

            <!-- Visualization: Tile Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_40%,#0891b2_0%,#020617_80%)]">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="relative w-full max-w-sm aspect-square bg-white shadow-[0_40px_100px_rgba(0,0,0,0.6)] border border-white/20 transform rotate-6 hover:rotate-0 transition-all duration-1000 group/tile">
                    <svg viewBox="0 0 200 200" class="w-full h-full fill-white stroke-slate-200" stroke-width="0.5">
                        <defs>
                            <pattern id="tileGrid" x="0" y="0" :width="isLarge ? '100' : '50'" :height="isLarge ? '100' : '50'" patternUnits="userSpaceOnUse">
                                <rect :width="isLarge ? '100' : '50'" :height="isLarge ? '100' : '50'" fill="none" stroke="#e2e8f0" />
                            </pattern>
                        </defs>
                        <rect width="200" height="200" fill="url(#tileGrid)" />
                        
                        <!-- Marble Texture (Conditional) -->
                        <g x-show="tileType === 'Marble'" class="opacity-30">
                            <path d="M0 50 Q 50 20 100 80 T 200 50" fill="none" stroke="#94a3b8" stroke-width="2" />
                            <path d="M50 0 Q 80 50 30 150" fill="none" stroke="#cbd5e1" stroke-width="1.5" />
                        </g>

                        <!-- Epoxy Grout Highlight -->
                        <g x-show="epoxyGrout" class="animate-pulse">
                            <line x1="0" y1="100" x2="200" y2="100" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="10 10" />
                            <line x1="100" y1="0" x2="100" y2="200" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="10 10" />
                        </g>
                    </svg>
                </div>
            </div>

            <div class="p-10 grid grid-cols-3 gap-6 relative z-30">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-cyan-500/50 transition-all shadow-xl text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Grout Flow</span>
                    <span class="text-2xl font-black italic tracking-tighter uppercase leading-none" :class="epoxyGrout ? 'text-cyan-400' : 'text-slate-500'">Epoxy</span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-cyan-500/50 transition-all shadow-xl text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Thermal Load</span>
                    <span class="text-2xl font-black italic tracking-tighter uppercase leading-none text-red-500 transition-all" :class="heatedFloor ? 'opacity-100' : 'opacity-20'">MAX</span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-cyan-500/50 transition-all shadow-xl text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Adhesion</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none">99.9%</span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner">
            <div class="mb-14 relative z-20 font-[Inter]">
                <div class="inline-block px-5 py-2 rounded-full bg-cyan-600 text-white mb-6 shadow-2xl leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] leading-none italic">Elite Tiling Systems</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Ceramic-Core X</h3>
                <p class="text-[12px] text-cyan-500 font-extrabold uppercase tracking-widest mt-6 opacity-60 leading-relaxed italic tracking-tighter leading-none shadow-sm shadow-sm italic shadow-sm italic">High-Density Surface Geometry v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-20 px-4">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none italic pl-1 italic">Total Geometry (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic" x-text="area"></span>
                    </div>
                    <div class="relative py-4">
                        <input type="range" x-model="area" min="5" max="300" step="5" 
                               class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-cyan-600 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <template x-for="type in ['Ceramic', 'Porcelain', 'Marble', 'Venetian']">
                        <button @click="tileType = type" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl"
                                :class="tileType === type ? 'bg-cyan-600 text-white border-cyan-500 scale-[1.03] shadow-cyan-900/50' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10 hover:text-white'">
                            <span x-text="type"></span>
                        </button>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between p-7 bg-white/5 rounded-3xl border border-white/10 hover:bg-white/10 transition-all group/btn shadow-xl">
                        <div class="text-left leading-none">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter">Heated Matrix</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 shadow-sm leading-none italic">Under-Grid Thermal Logic</span>
                        </div>
                        <button @click="heatedFloor = !heatedFloor" class="w-14 h-7 rounded-full relative transition-all shadow-inner" :class="heatedFloor ? 'bg-red-600 shadow-[0_0_15px_rgba(220,38,38,0.5)]' : 'bg-slate-700'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all" :style="heatedFloor ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Output -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl overflow-hidden relative group/confirm transition-all">
                <div class="absolute inset-x-0 h-[1px] bg-cyan-500 top-0 opacity-40"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 leading-none">
                    <div>
                        <span class="text-[12px] text-cyan-500 uppercase font-black block tracking-[0.2em] mb-4 italic leading-none tracking-tighter">Project Grid Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-cyan-600 hover:bg-cyan-500 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group/main relative overflow-hidden">
                    <span class="relative z-10 italic tracking-tighter">Initialize Ceramic Deployment</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 transform group-hover/main:translate-x-1.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
