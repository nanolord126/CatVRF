@php
    declare(strict_types=1);
    // Привязка к сервису домена ConstructionMaterials
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    wallArea: 30,
    inlineSize: 1.06,
    rollLength: 10,
    rapport: 0.64,
    wallpaperType: 'Vinyl',
    installation: true,
    correlationId: '{{ Str::uuid() }}',
    
    // Данные из WoodFlow / Material-Core сервиса
    get serviceData() {
        return {
            'Vinyl': { price: 2500, labor: 450 },
            'Non-woven': { price: 3800, labor: 550 },
            'Textile': { price: 8500, labor: 1200 },
            'Designer': { price: 15000, labor: 2500 }
        }[this.wallpaperType];
    },
    
    get totalRolls() {
        // Упрощенный расчет расхода с учетом раппорта
        let areaPerRoll = this.inlineSize * (this.rollLength - (this.rapport > 0 ? 0.5 : 0));
        return Math.ceil(this.wallArea / areaPerRoll);
    },
    
    get totalPrice() {
        let matCost = this.totalRolls * this.serviceData.price;
        let laborCost = this.installation ? (this.wallArea * this.serviceData.labor) : 0;
        return Math.round(matCost + laborCost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px] font-sans">
        
        <!-- Wallpaper Visual HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans">
            <div class="absolute top-10 left-10 z-40 font-sans">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-purple-500/30 font-sans">
                    <div class="w-2.5 h-2.5 bg-purple-500 rounded-full animate-pulse shadow-[0_0_10px_#a855f7]"></div>
                    <span class="text-[10px] text-purple-100 font-black uppercase tracking-widest italic tracking-tighter font-sans" x-text="'Pattern-Core: ' + wallpaperType"></span>
                </div>
            </div>

            <!-- Pattern Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#581c87_0%,#020617_100%)] font-sans">
                <div class="absolute inset-0 opacity-10 blur-sm bg-[url('https://www.transparenttextures.com/patterns/damask.png')]"></div>
                
                <div class="relative w-full max-w-sm aspect-[3/4] bg-white shadow-[0_50px_100px_rgba(0,0,0,0.6)] border border-white/10 overflow-hidden transform group-hover/viz:scale-[1.02] transition-all duration-1000">
                    <!-- Wallpaper Strip Representation -->
                    <div class="absolute inset-0 flex">
                        <template x-for="i in 3">
                            <div class="flex-1 border-r border-black/5 last:border-0 relative font-sans">
                                <!-- Rapport Patterns -->
                                <template x-for="j in 5">
                                    <div class="absolute inset-inline-0 opacity-20" :style="`inset-block-start: ${j * 20}%; block-size: 20px; background-image: radial-gradient(circle, #000 20%, transparent 20%); background-size: 10px 10px;`" x-show="rapport > 0"></div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Overlay for Material Texture -->
                    <div class="absolute inset-0 bg-purple-900/40 mix-blend-multiply opacity-60 transition-all font-sans" :style="`background-color: ${wallpaperType === 'Textile' ? '#4c1d95' : '#7e22ce'}`"></div>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-3 gap-6 relative z-30 font-sans italic">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Roll count</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="totalRolls"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl font-sans italic">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Glue Rate</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans italic">High</span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-purple-500 font-sans italic tracking-tighter">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Pro-Install</span>
                    <span class="text-2xl font-black italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter" :class="installation ? 'text-emerald-500 animate-pulse' : 'text-slate-500'">Active</span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner font-sans">
            <div class="mb-14 relative z-20 font-sans italic tracking-tighter">
                <div class="inline-block px-5 py-2 rounded-full bg-purple-600 text-white mb-6 shadow-2xl leading-none italic tracking-tighter font-sans">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] leading-none italic">Interior Pattern Array</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter font-sans italic tracking-tighter leading-none tracking-tighter font-sans italic tracking-tighter">Wallpaper-Core</h3>
                <p class="text-[12px] text-purple-500 font-extrabold uppercase tracking-widest mt-6 opacity-60 leading-relaxed italic tracking-tighter leading-none shadow-sm italic font-sans italic tracking-tighter leading-none italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic leading-none tracking-tighter font-sans italic">Pattern-Sync Distribution v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-20 px-4 font-sans italic tracking-tighter">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter pl-1 italic tracking-tighter font-sans italic">Wall Area (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic leading-none" x-text="wallArea"></span>
                    </div>
                    <div class="relative py-4 font-sans italic tracking-tighter">
                        <input type="range" x-model="wallArea" min="5" max="300" step="5" 
                               class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-purple-600 transition-all font-sans italic tracking-tighter leading-none italic leading-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic">
                    <template x-for="type in ['Vinyl', 'Non-woven', 'Textile', 'Designer']">
                        <button @click="wallpaperType = type" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter"
                                :class="wallpaperType === type ? 'bg-purple-600 text-white border-purple-500 scale-[1.03]' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10 hover:text-white'">
                            <span x-text="type"></span>
                        </button>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic">
                    <div class="space-y-4 font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic tracking-tighter pl-1 italic tracking-tighter leading-none italic font-sans italic">Roll Specs & Rapport</span>
                        <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic">
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center font-sans italic tracking-tighter leading-none italic">
                                <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-2 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic">Width (m)</span>
                                <span class="text-lg text-white font-black italic font-sans italic tracking-tighter leading-none italic" x-text="inlineSize"></span>
                            </div>
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center font-sans italic tracking-tighter leading-none italic">
                                <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-2 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic">Rapport (cm)</span>
                                <span class="text-lg text-white font-black italic font-sans italic tracking-tighter leading-none italic" x-text="rapport * 100"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Output -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl overflow-hidden relative group/confirm transition-all font-sans italic tracking-tighter leading-none italic">
                <div class="absolute inset-x-0 h-[1px] bg-purple-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 leading-none font-sans italic tracking-tighter leading-none italic">
                    <div class="font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[12px] text-purple-500 uppercase font-black block tracking-[0.2em] mb-4 italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic">Wallpaper Spec Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic leading-none tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-purple-600 hover:bg-purple-500 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group/main relative overflow-hidden font-sans italic tracking-tighter leading-none italic">
                    <span class="relative z-10 italic tracking-tighter font-sans italic tracking-tighter leading-none italic leading-none tracking-tighter">Initialize Pattern Deployment</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 transform group-hover/main:rotate-12 transition-transform font-sans italic tracking-tighter leading-none italic" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

