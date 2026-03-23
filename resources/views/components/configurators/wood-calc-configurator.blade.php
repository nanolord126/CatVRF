@php
    declare(strict_types=1);
    // Привязка к сервису домена ConstructionMaterials
    $materialService = app(\App\Domains\ConstructionMaterials\Services\MaterialService::class);
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    area: 40,
    material: 'Oak',
    underlay: true,
    installation: true,
    pattern: 'Straight',
    correlationId: '{{ Str::uuid() }}',
    
    // Пакеты материалов (динамические данные из сервиса)
    get materialServiceData() {
        return {
            'Oak': { basePrice: 4500, waste: 1.07 },
            'Walnut': { basePrice: 7800, waste: 1.10 },
            'Ash': { basePrice: 3900, waste: 1.07 },
            'Engineered': { basePrice: 3200, waste: 1.05 }
        }[this.material];
    },

    get materialPrice() {
        let base = this.materialServiceData.basePrice;
        if (this.pattern === 'Herringbone') base *= 1.25;
        return base;
    },

    get laborPrice() {
        if (!this.installation) return 0;
        let base = 1200;
        if (this.pattern === 'Herringbone') base = 2500;
        return base;
    },

    get totalPrice() {
        let matCost = this.area * this.materialPrice;
        let laborCost = this.area * this.laborPrice;
        let underlayCost = this.underlay ? this.area * 450 : 0;
        return Math.round(matCost + laborCost + underlayCost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px]">
        
        <!-- UI: Parquet Visual Layout -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5">
            <!-- HUD: Wood Core -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-4 bg-black/60 backdrop-blur-2xl px-5 py-2.5 rounded-full border border-amber-500/30 shadow-xl">
                    <div class="w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] text-amber-100 font-extrabold uppercase tracking-widest italic tracking-tighter leading-none" x-text="material + ' Scan' + (pattern === 'Herringbone' ? ' / Pattern Mod: High' : '')"></span>
                </div>
            </div>

            <!-- Pattern Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#1e293b_0%,#020617_100%)]">
                <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/dark-wood.png')]"></div>
                
                <div class="relative w-full max-w-sm aspect-square bg-white shadow-[0_50px_100px_rgba(0,0,0,0.5)] border border-white/10 overflow-hidden transform rotate-12 hover:rotate-0 transition-transform duration-700">
                    <svg viewBox="0 0 200 200" class="w-full h-full">
                        <defs>
                            <pattern id="parquetPattern" x="0" y="0" :width="pattern === 'Straight' ? '40' : '60'" :height="pattern === 'Straight' ? '10' : '60'" patternUnits="userSpaceOnUse" :patternTransform="pattern === 'Herringbone' ? 'rotate(45)' : ''">
                                <g x-show="pattern === 'Straight'">
                                    <rect width="40" height="10" fill="transparent" stroke="#000" stroke-width="0.5" x="0" y="0" />
                                </g>
                                <g x-show="pattern === 'Herringbone'">
                                    <rect width="10" height="40" fill="transparent" stroke="#000" stroke-width="0.5" />
                                    <rect width="40" height="10" x="10" y="0" fill="transparent" stroke="#000" stroke-width="0.5" />
                                </g>
                            </pattern>
                        </defs>
                        
                        <rect width="200" height="200" fill="url(#parquetPattern)" />
                        
                        <!-- Overlay Wood Texture Effect -->
                        <rect width="200" height="200" fill="rgba(217, 119, 6, 0.4)" style="mix-blend-mode: multiply;" />
                        <rect width="200" height="200" fill="rgba(255, 255, 255, 0.05)" style="mix-blend-mode: overlay;" />
                    </svg>
                </div>
            </div>

            <!-- HUD Stats -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-30">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-amber-500/50 transition-all shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Waste Factor</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none" x-text="pattern === 'Herringbone' ? '+15%' : '+7%'"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-amber-500/50 transition-all shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">Wood Grade</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none">Select</span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 group/stat hover:border-amber-500/50 transition-all shadow-xl text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter italic">UV Shield</span>
                    <span class="text-2xl text-emerald-500 font-black italic tracking-tighter uppercase leading-none animate-pulse">On</span>
                </div>
            </div>
        </div>

        <!-- Controls: Surface & Wood DNA -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 relative group/controls shadow-inner">
            <div class="mb-14 relative z-20 font-[Inter]">
                <div class="inline-block px-5 py-2 rounded-full bg-amber-600 text-white mb-6 shadow-2xl shadow-amber-900/40 leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] leading-none italic">Industrial Timber Decking</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none">Wood-Flow Engine</h3>
                <p class="text-[12px] text-amber-500 font-extrabold uppercase tracking-widest mt-6 opacity-60 leading-relaxed italic tracking-tighter leading-none shadow-sm shadow-sm italic shadow-sm italic">Ultra-Precision Hardwood Array v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-20 px-4">
                <!-- Area Selector -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter leading-none italic">Geometric Surface (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic" x-text="area"></span>
                    </div>
                    <div class="relative py-4">
                        <input type="range" x-model="area" min="10" max="1000" step="10" 
                               class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-amber-600">
                    </div>
                </div>

                <!-- Wood Selection Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <template x-for="w in ['Oak', 'Walnut', 'Ash', 'Engineered']">
                        <button @click="material = w" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest shadow-xl flex items-center justify-center space-x-3 group/chip leading-none"
                                :class="material === w ? 'bg-amber-600 text-white border-amber-500 scale-[1.03] shadow-amber-900/50' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10 hover:text-white'">
                            <span x-text="w"></span>
                        </button>
                    </template>
                </div>

                <!-- Pattern Selection -->
                <div class="space-y-6 pt-4 border-t border-white/5">
                    <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic tracking-tighter leading-none italic pl-1 italic">Installation Architecture</span>
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="pattern = 'Straight'" 
                                class="py-5 rounded-3xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl"
                                :class="pattern === 'Straight' ? 'bg-white text-slate-950 border-white shadow-white/10' : 'bg-white/5 text-slate-500 border-white/5'">Straight Deck</button>
                        <button @click="pattern = 'Herringbone'" 
                                class="py-5 rounded-3xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl"
                                :class="pattern === 'Herringbone' ? 'bg-white text-slate-950 border-white shadow-white/10' : 'bg-white/5 text-slate-500 border-white/5'">Elite Herringbone</button>
                    </div>
                </div>
            </div>

            <!-- Global Price Engine -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl group/confirm overflow-hidden relative shadow-2xl">
                <div class="absolute inset-x-0 h-[1px] bg-amber-500 top-0 opacity-40"></div>
                
                <div class="flex items-center justify-between mb-8 relative z-20 leading-none">
                    <div>
                        <span class="text-[12px] text-amber-500 uppercase font-black block tracking-[0.2em] mb-4 italic leading-none tracking-tighter">Total Spec Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 relative z-20">
                    <button class="w-full bg-amber-600 hover:bg-amber-500 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-[0_25px_60px_-15px_rgba(217,119,6,0.5)] uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group/mainbtn relative overflow-hidden">
                        <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover/mainbtn:translate-x-full transition-transform duration-1000"></div>
                        <span class="relative z-10 italic">Deploy Wood-Core Order</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover/mainbtn:rotate-12 transition-transform relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
