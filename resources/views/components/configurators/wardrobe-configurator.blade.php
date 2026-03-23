@php
    declare(strict_types=1);
    
    /** @var \App\Domains\ConstructionMaterials\Services\MaterialCalculatorService $calculator */
    $calculator = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
    $correlationId = (string) \Illuminate\Support\Str::uuid();
@endphp

<div x-data="{
    width: 2400,
    height: 2600,
    sections: 3,
    doors: 3,
    material: 'LDSP_Egger',
    fittings: 'Blum',
    elements: [],
    
    // Wood-Core v.2026 Material Constants
    get serviceData() {
        return {
            'LDSP_Egger': { price: 4500, labor: 1200 },
            'MDF_Painted': { price: 8500, labor: 2500 },
            'Veneer': { price: 15000, labor: 4500 },
            'Solid_Wood': { price: 25000, labor: 8000 }
        }[this.material];
    },
    
    get totalArea() {
        // Calculation of approximate board area (m2)
        return ((this.width * this.height) / 1000000) * 4.5;
    },
    
    get totalPrice() {
        let matCost = this.totalArea * this.serviceData.price;
        let elementCost = this.elements.reduce((acc, el) => acc + el.price, 0);
        let fittingMarkup = this.fittings === 'Blum' ? 1.3 : 1.1;
        return Math.round((matCost + elementCost) * fittingMarkup + (this.totalArea * this.serviceData.labor));
    },

    addElement(type) {
        const types = {
            'shelf': { name: 'Shelf', price: 950, h: 2, color: '#6366f1' },
            'rod': { name: 'Rod', price: 1400, h: 4, color: '#f8fafc' },
            'drawer': { name: 'Drawer', price: 4200, h: 20, color: '#4338ca' }
        };
        if (this.elements.length < 15) {
            this.elements.push({
                ...types[type],
                y: Math.random() * 80 + 10,
                x: (Math.floor(Math.random() * this.sections) * (100 / this.sections)) + 2
            });
        }
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="inline-size-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 block-size-full min-block-size-[850px]">
        
        <!-- Wardrobe Visual HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter">
            <div class="absolute inset-block-start-10 inset-inline-start-10 z-40">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-indigo-500/30 font-sans tracking-tighter">
                    <div class="inline-size-2.5 block-size-2.5 bg-indigo-500 rounded-full animate-pulse shadow-[0_0_10px_#6366f1]"></div>
                    <span class="text-[10px] text-indigo-100 font-black uppercase tracking-widest" x-text="'Wood-Core: ' + material.replace('_', ' ')"></span>
                </div>
            </div>

            <!-- SVG Projection -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#312e81_0%,#020617_100%)]">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="relative inline-size-full max-inline-size-sm aspect-[3/4] transition-all duration-700 transform group-hover/viz:scale-[1.02]">
                    <svg viewBox="0 0 100 130" class="inline-size-full block-size-full drop-shadow-[0_30px_60px_rgba(0,0,0,0.8)]">
                        <!-- Cabinet Frame -->
                        <rect x="2" y="2" width="96" height="126" fill="none" stroke="#6366f1" stroke-width="0.5" class="opacity-50" />
                        <rect x="0" y="0" width="100" height="130" fill="none" stroke="#6366f1" stroke-width="1" />
                        
                        <!-- Internal Sections -->
                        <template x-for="i in parseInt(sections)-1">
                            <line :x1="(100/sections) * i" y1="0" :x2="(100/sections) * i" y2="130" stroke="#6366f1" stroke-width="0.2" stroke-dasharray="1 1" />
                        </template>

                        <!-- Dynamic Elements -->
                        <template x-for="el in elements">
                            <rect :x="el.x" :y="el.y" :width="(100/sections)-4" :height="el.h" :fill="el.color" class="opacity-60 animate-pulse" />
                        </template>

                        <!-- Glass Overlay (Doors) -->
                        <rect x="0" y="0" width="100" height="130" fill="url(#glassGrad)" class="opacity-20 pointer-events-none" />
                        
                        <defs>
                            <linearGradient id="glassGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:white;stop-opacity:0.2" />
                                <stop offset="50%" style="stop-color:white;stop-opacity:0" />
                                <stop offset="100%" style="stop-color:white;stop-opacity:0.1" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>

            <div class="p-10 grid grid-cols-3 gap-6 relative z-30 font-sans tracking-tighter leading-none italic">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block mb-3 leading-none tracking-tighter">Load Index</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none" x-text="elements.length * 12 + 'kg'"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block mb-3 leading-none tracking-tighter">Precision</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none">0.1mm</span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-block-start-4 border-block-start-indigo-500">
                    <span class="text-[9px] text-slate-500 uppercase font-black block mb-3 leading-none tracking-tighter">Fit Status</span>
                    <span class="text-2xl text-emerald-500 font-black italic tracking-tighter uppercase leading-none animate-pulse">Optimal</span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner">
            <div class="margin-block-end-14 relative z-20">
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none">Furniture-Core</h3>
                <p class="text-[10px] text-indigo-500 font-extrabold uppercase mt-3 opacity-60 leading-none tracking-tighter italic">Modular Storage System v.2026</p>
            </div>

            <div class="flex-grow space-y-12 relative z-20 font-sans tracking-tighter leading-none italic">
                <!-- Dimensions -->
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex justify-between items-end margin-block-end-1 padding-inline-end-2">
                            <span class="text-[10px] text-slate-500 uppercase font-black block leading-none tracking-tighter italic">Width (mm)</span>
                            <span class="text-xl font-black text-white" x-text="width"></span>
                        </div>
                        <input type="range" x-model="width" min="600" max="5000" step="100" class="inline-size-full block-size-1 bg-white/10 rounded-full appearance-none accent-indigo-600">
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end margin-block-end-1 padding-inline-end-2">
                            <span class="text-[10px] text-slate-500 uppercase font-black block leading-none tracking-tighter italic">Height (mm)</span>
                            <span class="text-xl font-black text-white" x-text="height"></span>
                        </div>
                        <input type="range" x-model="height" min="1500" max="3200" step="50" class="inline-size-full block-size-1 bg-white/10 rounded-full appearance-none accent-indigo-600">
                    </div>
                </div>

                <!-- material Array -->
                <div class="space-y-4">
                    <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic">Fascia Matrix</span>
                    <div class="grid grid-cols-2 gap-4">
                        <template x-for="type in ['LDSP_Egger', 'MDF_Painted', 'Veneer', 'Solid_Wood']">
                            <button @click="material = type" 
                                    class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl"
                                    :class="material === type ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10 hover:text-white'">
                                <span x-text="type.replace('_', ' ')"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Internal Modules -->
                <div class="space-y-4">
                    <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none italic">Storage Injectors</span>
                    <div class="grid grid-cols-3 gap-4">
                        <button @click="addElement('shelf')" class="py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black text-[9px] hover:bg-indigo-600/20 transition-all uppercase italic tracking-tighter">+ Shelf</button>
                        <button @click="addElement('rod')" class="py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black text-[9px] hover:bg-indigo-600/20 transition-all uppercase italic tracking-tighter">+ Rod</button>
                        <button @click="addElement('drawer')" class="py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black text-[9px] hover:bg-indigo-600/20 transition-all uppercase italic tracking-tighter">+ Drawer</button>
                    </div>
                </div>

                <!-- Logic Modules -->
                <div class="flex items-center justify-between p-6 bg-white/5 rounded-[2rem] border border-white/10">
                    <div>
                        <span class="text-white text-sm font-bold italic block leading-none underline decoration-indigo-500/50 underline-offset-4 uppercase">Premium Hardware</span>
                        <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter margin-block-start-1 italic">Blum Motion / Soft-Close</p>
                    </div>
                    <button @click="fittings = fittings === 'Blum' ? 'Standard' : 'Blum'" class="inline-size-14 block-size-7 rounded-full relative transition-all shadow-inner" :class="fittings === 'Blum' ? 'bg-indigo-600' : 'bg-slate-700'">
                        <div class="absolute inset-block-start-1.5 inline-size-4 block-size-4 bg-white rounded-full transition-all shadow-lg" :style="fittings === 'Blum' ? 'inset-inline-start: 34px' : 'inset-inline-start: 6px'"></div>
                    </button>
                </div>
            </div>

            <!-- Price Output -->
            <div class="margin-block-start-14 padding-block-start-8 border-block-start border-white/10 relative z-20 font-sans tracking-tighter leading-none italic">
                <div class="flex items-center justify-between margin-block-end-10 leading-none">
                    <div>
                        <span class="text-[12px] text-indigo-500 uppercase font-black block tracking-widest margin-block-end-4 italic leading-none font-sans tracking-tighter">Engineered Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl underline decoration-indigo-500 decoration-4 underline-offset-8" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="inline-size-full bg-indigo-600 hover:bg-indigo-500 text-slate-950 font-black py-7 rounded-3xl transition-all shadow-[0_20px_40px_rgba(99,102,241,0.3)] uppercase italic text-lg tracking-widest flex items-center justify-center space-x-5 active:scale-95 group/main relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover/main:translate-x-full transition-transform duration-700 leading-none"></div>
                    <span class="relative z-10 italic tracking-tighter leading-none">Generate Technical Spec</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="inline-size-6 block-size-6 relative z-10 transform group-hover/main:rotate-12 transition-transform leading-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
