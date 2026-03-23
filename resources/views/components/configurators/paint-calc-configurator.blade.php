@php
    declare(strict_types=1);
    
    /** @var \App\Domains\ConstructionMaterials\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
    $correlationId = (string) \Illuminate\Support\Str::uuid();
@endphp

<div x-data="{
    surfaceArea: 50,
    paintLayers: 2,
    finishType: 'Matte',
    primerRequired: true,
    customTint: false,
    
    // Chroma-Core v.2026 Material Constants
    get serviceData() {
        return {
            'Matte': { consumption: 0.1, price: 850 },
            'Satin': { consumption: 0.12, price: 1200 },
            'Glossy': { consumption: 0.15, price: 1500 },
            'Soft-Touch': { consumption: 0.18, price: 2200 }
        }[this.finishType];
    },
    
    get totalLiters() {
        let consumption = this.serviceData.consumption;
        return ((this.surfaceArea * consumption) * this.paintLayers).toFixed(1);
    },
    
    get totalPrice() {
        let basePrice = this.serviceData.price;
        if (this.customTint) basePrice += 450;
        
        let cost = (this.surfaceArea * basePrice) * (this.paintLayers * 0.8);
        if (this.primerRequired) cost += this.surfaceArea * 350;
        return Math.round(cost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[750px]">
        
        <!-- Paint Visual HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter">
            <!-- HUD Tooltip -->
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-indigo-500/30">
                    <div class="w-2.5 h-2.5 bg-indigo-500 rounded-full animate-pulse shadow-[0_0_10px_#6366f1]"></div>
                    <span class="text-[10px] text-indigo-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Chroma-Core: ' + finishType"></span>
                </div>
            </div>

            <!-- Surface Visualization -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#312e81_0%,#020617_100%)]">
                <div class="absolute inset-0 opacity-[0.05] pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/concrete-wall.png')]"></div>
                
                <div class="relative w-full max-w-sm aspect-[4/5] bg-white rounded-2xl shadow-[0_50px_100px_rgba(0,0,0,0.6)] border border-white/10 overflow-hidden transform group-hover/viz:scale-[1.02] transition-all duration-1000">
                    <!-- Main Color -->
                    <div class="absolute inset-0 transition-all duration-700" 
                         :class="finishType === 'Glossy' ? 'brightness-110' : 'brightness-100'"
                         :style="`background-color: ${customTint ? '#6366f1' : '#cbd5e1'}`">
                    </div>
                    
                    <!-- Gloss / Reflectance Overlay -->
                    <div x-show="finishType === 'Glossy'" 
                         class="absolute inset-0 bg-gradient-to-tr from-white/20 via-white/40 to-transparent opacity-60 animate-pulse"></div>
                    
                    <!-- Roller Scanline -->
                    <div class="absolute inset-inline-start-1/4 inline-size-1/2 block-size-full bg-white/10 blur-3xl animate-[pulse_4s_infinite]"></div>

                    <!-- Telemetry Labels -->
                    <div class="absolute inset-block-end-8 inset-inline-start-8 inset-inline-end-8 space-y-4 font-sans italic tracking-tighter">
                        <div class="block-size-[2px] bg-white/10 inline-size-full overflow-hidden">
                            <div class="block-size-full bg-indigo-500 transition-all duration-1000" 
                                 :style="`inline-size: ${finishType === 'Glossy' ? '100%' : (finishType === 'Satin' ? '70%' : '30%')}`"></div>
                        </div>
                        <div class="flex justify-between items-center text-[10px] font-black uppercase text-white/40 tracking-widest">
                            <span x-text="finishType"></span>
                            <span>Reflectance Index</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Module -->
            <div class="p-10 grid grid-cols-3 gap-6 relative z-30 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Liquid Unit</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter" x-text="totalLiters + ' L'"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl font-sans italic tracking-tighter">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Opacity Index</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter" x-text="paintLayers > 1 ? '100%' : '65%'"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-indigo-500 font-sans italic tracking-tighter">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Dry Phase</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none">4.5 H</span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner">
            <div class="mb-14 relative z-20 font-sans italic">
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter leading-none italic">Paint-Core</h3>
                <p class="text-[10px] text-indigo-500 font-extrabold uppercase tracking-widest mt-3 opacity-60 italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic">Chroma-Logic Distribution v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-20 font-sans italic tracking-tighter leading-none italic">
                <!-- Area Calculation -->
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic leading-none font-sans italic tracking-tighter leading-none italic">Surface Matrix (M²)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic leading-none font-sans italic tracking-tighter leading-none italic" x-text="surfaceArea"></span>
                    </div>
                    <input type="range" x-model="surfaceArea" min="5" max="500" step="5" 
                           class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-indigo-600 transition-all font-sans italic tracking-tighter leading-none italic leading-none font-sans italic tracking-tighter leading-none italic">
                </div>

                <!-- Coating Array -->
                <div class="space-y-4">
                    <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic tracking-tighter pl-1 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic">Finish Algorithm</span>
                    <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic">
                        <template x-for="type in ['Matte', 'Satin', 'Glossy', 'Soft-Touch']">
                            <button @click="finishType = type" 
                                    class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic"
                                    :class="finishType === type ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10 hover:text-white'">
                                <span x-text="type"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Logic Modules -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-[2rem] border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none underline decoration-indigo-500/50 underline-offset-4 uppercase font-sans italic tracking-tighter leading-none italic">Primer Integration</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1 italic font-sans italic tracking-tighter leading-none italic">Structural Base Layer</p>
                        </div>
                        <button @click="primerRequired = !primerRequired" class="w-14 h-7 rounded-full relative transition-all shadow-inner" :class="primerRequired ? 'bg-indigo-600' : 'bg-slate-700'">
                            <div class="absolute top-1.5 w-4 h-4 bg-white rounded-full transition-all shadow-lg" :style="primerRequired ? 'inset-inline-start: 34px' : 'inset-inline-start: 6px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-[2rem] border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none underline decoration-indigo-500/50 underline-offset-4 uppercase font-sans italic tracking-tighter leading-none italic">Custom Tinting</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1 italic font-sans italic tracking-tighter leading-none italic">Precision Chroma Mix</p>
                        </div>
                        <button @click="customTint = !customTint" class="w-14 h-7 rounded-full relative transition-all shadow-inner" :class="customTint ? 'bg-indigo-600' : 'bg-slate-700'">
                            <div class="absolute top-1.5 w-4 h-4 bg-white rounded-full transition-all shadow-lg" :style="customTint ? 'inset-inline-start: 34px' : 'inset-inline-start: 6px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Output -->
            <div class="mt-14 pt-8 border-t border-white/10 relative z-20 font-sans italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic">
                <div class="flex items-center justify-between mb-10 leading-none">
                    <div>
                        <span class="text-[12px] text-indigo-500 uppercase font-black block tracking-widest mb-4 italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic">Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl underline decoration-indigo-500 decoration-4 underline-offset-8 font-sans italic tracking-tighter leading-none italic leading-none font-sans italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-indigo-600 hover:bg-indigo-500 text-slate-950 font-black py-7 rounded-3xl transition-all shadow-[0_20px_40px_rgba(99,102,241,0.3)] uppercase italic text-lg tracking-widest flex items-center justify-center space-x-5 active:scale-95 group/main relative overflow-hidden font-sans italic tracking-tighter leading-none italic leading-none">
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover/main:translate-x-full transition-transform duration-700 font-sans italic tracking-tighter leading-none italic leading-none"></div>
                    <span class="relative z-10 italic tracking-tighter font-sans italic tracking-tighter leading-none italic leading-none font-sans italic tracking-tighter">Initialize Chroma Deployment</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10 transform group-hover/main:rotate-12 transition-transform font-sans italic tracking-tighter leading-none italic leading-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
