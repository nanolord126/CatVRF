@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом кровли и кровельных материалов
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { inlineSize: 10, blockSize: 12, pitch: 35, overhang: 0.5 },
    roofMaterial: 'Metal Tile',
    insulation: true,
    drainage: true,
    correlationId: '{{ Str::uuid() }}',

    get projectedArea() { return this.config.inlineSize * this.config.blockSize; },
    
    get slopeArea() {
        let radians = this.config.pitch * (Math.PI / 180);
        return Math.ceil(this.projectedArea / Math.cos(radians));
    },

    get serviceData() {
        return {
            'Metal Tile': { price: 850, weight: 5 },
            'Soft Tiles': { price: 1200, weight: 10 },
            'Ceramic': { price: 3500, weight: 45 },
            'Decking': { price: 600, weight: 4 }
        }[this.roofMaterial];
    },

    get totalPrice() {
        let matCost = this.slopeArea * this.serviceData.price;
        let insulationCost = this.insulation ? (this.slopeArea * 450) : 0;
        let drainageCost = this.drainage ? (this.slopeArea * 250) : 0;
        return Math.round(matCost + insulationCost + drainageCost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Roof Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-indigo-500/30">
                    <div class="w-2.5 h-2.5 bg-indigo-600 rounded-full animate-pulse shadow-[0_0_10px_#4f46e5]"></div>
                    <span class="text-[10px] text-indigo-100 font-black uppercase tracking-widest italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" x-text="'Sky-Core: ' + roofMaterial"></span>
                </div>
            </div>

            <!-- Roof Pitch Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#312e81_0%,#020617_100%)] font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="relative w-full max-w-md aspect-video group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none italic uppercase">
                    <svg viewBox="0 0 500 300" class="w-full drop-shadow-2xl">
                        <path :d="`M 50 250 L 250 ${250 - (config.pitch * 3)} L 450 250`" fill="none" stroke="#6366f1" stroke-width="8" stroke-linecap="round" />
                        <line x1="50" y1="250" x2="450" y2="250" stroke="white" stroke-width="1" stroke-dasharray="5,5" opacity="0.3" />
                    </svg>
                    <div class="absolute inset-x-0 bottom-0 text-center font-sans italic tracking-tighter leading-none italic uppercase">
                        <span class="text-4xl font-black text-white italic tracking-tighter italic tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic uppercase" x-text="config.pitch + '°'"></span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Surface Area (M²)</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="slopeArea"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-indigo-500">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Total Price</span>
                    <span class="text-3xl text-indigo-400 font-black italic tracking-tighter uppercase leading-none font-sans" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase">
                <div class="inline-block px-5 py-2 rounded-full bg-indigo-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none">Top-Side Flux Matrix</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Roof Shell</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none italic">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest">Slope Angle (°)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic" x-text="config.pitch"></span>
                    </div>
                    <input type="range" x-model="config.pitch" min="0" max="60" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-indigo-500 transition-all font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none">
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic">
                    <template x-for="mat in ['Metal Tile', 'Soft Tiles', 'Ceramic', 'Decking']">
                        <button @click="roofMaterial = mat" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic":class="roofMaterial === mat ? 'bg-indigo-700 text-white border-indigo-500' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="mat"></span>
                        </button>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-indigo-500 transition-all font-sans italic tracking-tighter leading-none">
                        <div class="text-left font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter">Turbo Insulation</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic">Rockwool 200mm Layer</span>
                        </div>
                        <button @click="insulation = !insulation" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="insulation ? 'bg-indigo-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md" :style="insulation ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-indigo-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none italic">
                    <div class="font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[12px] text-indigo-500 uppercase font-black block tracking-[0.2em] mb-4 italic font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-widest font-sans italic tracking-tighter leading-none italic">Sky Shell Integration Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-indigo-700 hover:bg-indigo-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic">
                    <span>Deploy Roof Matrix</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                    stroke-width="4" 
                    stroke-linejoin="round"
                    class="transition-all duration-500"
                />
                
                <!-- Покрытие (текстура/паттерн) -->
                <path 
                    :d="`M 110 305 L 300 ${310 - (config.pitch * 5)} L 490 305 L 490 315 L 300 ${320 - (config.pitch * 5)} L 110 315 Z`" 
                    :fill="config.material === 'metal' ? '#475569' : '#991b1b'" 
                    class="opacity-80 transition-all duration-500"
                />

                <!-- Размеры (Аннотации) -->
                <line x1="100" y1="330" x2="500" y2="330" stroke="white" stroke-width="1" opacity="0.3" />
                <text x="300" y="350" fill="white" font-size="12" text-anchor="middle" class="italic opacity-50" x-text="config.length + ' мм'"></text>
                
                <text 
                    x="180" 
                    :y="300 - (config.pitch * 2.5)" 
                    fill="#6366f1" 
                    font-size="14" 
                    font-weight="bold" 
                    class="italic" 
                    x-text="config.pitch + '°'"
                ></text>
            </svg>

            <!-- Техническая панель -->
            <div class="absolute bottom-10 left-10 grid grid-cols-2 gap-4">
                <div class="p-3 bg-white/5 border border-white/10 rounded-2xl">
                    <span class="text-[8px] text-slate-500 uppercase font-black block mb-1">Длина стропила</span>
                    <span class="text-white font-bold italic" x-text="results.rafterLength + ' мм'"></span>
                </div>
                <div class="p-3 bg-white/5 border border-white/10 rounded-2xl">
                    <span class="text-[8px] text-slate-500 uppercase font-black block mb-1">Снеговая нагрузка</span>
                    <span class="text-emerald-400 font-bold italic" x-text="results.snowLoad + ' кг/м²'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-indigo-500 pb-4">
                Roofing <span class="text-indigo-500">Shield 2.0</span>
            </h1>

            <div class="grid grid-cols-2 gap-4">
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Тип кровли</span>
                    <select x-model="config.type" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="gable">Двускатная</option>
                        <option value="hip">Вальмовая</option>
                        <option value="shed">Односкатная</option>
                    </select>
                </label>
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Материал</span>
                    <select x-model="config.material" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none">
                        <option value="metal">Металлочерепица</option>
                        <option value="soft">Мягкая кровля</option>
                        <option value="ceramic">Керамика (Classic)</option>
                    </select>
                </label>
            </div>

            <div class="space-y-6">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-slate-400 text-xs italic font-bold">Ширина здания (W):</span>
                        <span class="text-white font-black" x-text="config.width + ' мм'"></span>
                    </div>
                    <input type="range" x-model.number="config.width" min="3000" max="15000" step="100" class="w-full h-1 bg-white/10 rounded-full appearance-none accent-indigo-500">
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-slate-400 text-xs italic font-bold">Угол наклона (Pitch):</span>
                        <span class="text-white font-black" x-text="config.pitch + '°'"></span>
                    </div>
                    <input type="range" x-model.number="config.pitch" min="5" max="60" class="w-full h-1 bg-white/10 rounded-full appearance-none accent-indigo-500">
                </div>
            </div>

            <div class="bg-indigo-600/10 p-6 rounded-3xl border border-indigo-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Вес покрытия</span>
                        <span class="text-xl font-bold text-white italic" x-text="results.totalWeight + ' кг'"></span>
                    </div>
                    <div class="border-l border-white/10 pl-6">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Листов / Рулонов</span>
                        <span class="text-xl font-bold text-white italic" x-text="results.unitsQty + ' шт.'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end pt-4 border-t border-white/5">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Итого за материалы:</span>
                    <span class="text-3xl font-black text-white" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveRoof()" class="w-full py-5 bg-white text-slate-900 rounded-3xl font-black italic tracking-widest hover:bg-indigo-50 transition-all flex items-center justify-center gap-3 active:scale-95 shadow-2xl shadow-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                СФОРМИРОВАТЬ ЗАКАЗ НА КРОВЛЮ
            </button>
        </div>
    </div>
</div>
