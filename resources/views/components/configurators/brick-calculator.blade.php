@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом кирпича и кладочных растворов
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    wall: { inlineSize: 12, blockSize: 3 },
    brickSize: 'Standard', // 250x120x65
    thickness: '1', // Кирпич в 250мм
    waste: 5,
    mortarRatio: '1:3',
    correlationId: '{{ Str::uuid() }}',

    get surfaceArea() { return this.wall.inlineSize * this.wall.blockSize; },
    
    // Данные из WoodFlow / Brick-Core
    get serviceData() {
        return {
            'Standard': { size: [250, 120, 65], price: 25, mortar: 0.0002 },
            'Double': { size: [250, 120, 138], price: 45, mortar: 0.0004 },
            'Clinker': { size: [240, 115, 71], price: 85, mortar: 0.00025 }
        }[this.brickSize];
    },

    get brickCount() {
        let vol = this.surfaceArea * (parseFloat(this.thickness) * 0.25);
        let brickVol = (this.serviceData.size[0] * this.serviceData.size[1] * this.serviceData.size[2]) / 1_000_000_000;
        return Math.ceil((vol / brickVol) * (1 + this.waste/100));
    },

    get totalCost() {
        return Math.round(this.brickCount * this.serviceData.price);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[700px] font-sans italic tracking-tighter">
        
        <!-- Brick Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 font-sans italic tracking-tighter">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-red-500/30">
                    <div class="w-2.5 h-2.5 bg-red-600 rounded-full animate-pulse shadow-[0_0_10px_#dc2626]"></div>
                    <span class="text-[10px] text-red-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Structure-Core: ' + brickSize"></span>
                </div>
            </div>

            <!-- Wall Grid Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-gradient-to-br from-red-950/40 to-slate-950">
                <div class="grid relative gap-1 border-4 border-red-900/20 p-2 shadow-2xl rounded-lg"
                     :style="`grid-template-columns: repeat(10, 1fr); inline-size: ${Math.min(wall.inlineSize * 40, 500)}px; block-size: 200px;`">
                    <template x-for="i in 50">
                        <div class="bg-red-800/40 border border-red-900/50 rounded-sm hover:bg-orange-500/50 transition-colors duration-300"></div>
                    </template>
                </div>
                <div class="absolute inset-x-0 bottom-0 block-size-32 bg-gradient-to-t from-slate-950 to-transparent"></div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 font-sans italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Unit Count (pcs)</span>
                    <span class="text-4xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="brickCount"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-red-600">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Total Price</span>
                    <span class="text-3xl text-red-500 font-black italic tracking-tighter uppercase leading-none font-sans" x-text="formatPrice(totalCost)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner font-sans italic tracking-tighter leading-none uppercase">
            <div class="mb-14 font-sans italic tracking-tighter leading-none uppercase">
                <div class="inline-block px-5 py-2 rounded-full bg-red-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em]">Material-Core Flux</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Brick Calc</h3>
            </div>

            <div class="flex-grow space-y-12 px-2 font-sans italic tracking-tighter leading-none">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter">Length (Meters)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic" x-text="wall.inlineSize"></span>
                    </div>
                    <input type="range" x-model="wall.inlineSize" min="1" max="50" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-red-600">
                </div>

                <div class="grid grid-cols-3 gap-4 font-sans italic tracking-tighter leading-none italic">
                    <template x-for="size in ['Standard', 'Double', 'Clinker']">
                        <button @click="brickSize = size" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans"
                                :class="brickSize === size ? 'bg-red-700 text-white border-red-600 scale-[1.03]' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="size"></span>
                        </button>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none italic">
                    <label class="block group font-sans italic tracking-tighter leading-none italic">
                        <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mb-4 italic leading-none tracking-tighter">Waste Factor (%)</span>
                        <input type="number" x-model="waste" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-white font-black italic tracking-tighter">
                    </label>
                </div>
            </div>

            <button class="mt-auto w-full bg-red-700 hover:bg-red-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group/main font-sans italic tracking-tighter leading-none italic">
                <span>Confirm Material Batch</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>
    </div>
</div>

                    <label class="block">
                        <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">Тип кирпича</span>
                        <select x-model="brick.type" @change="updateBrick()" class="mt-1 w-full bg-black/40 border-white/10 text-white rounded-lg italic">
                            <option value="single">Одинарный (250х120х65)</option>
                            <option value="one_and_half">Полуторный (250х120х88)</option>
                            <option value="double">Двойной (250х120х138)</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">Толщина шва (мм)</span>
                        <input type="number" x-model.number="config.jointThickness" class="mt-1 w-full bg-black/40 border-white/10 text-white rounded-lg">
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">Толщина стен (кирпича)</span>
                    <select x-model="config.wallThicknessCoef" class="mt-1 w-full bg-black/40 border-white/10 text-white rounded-lg italic">
                        <option value="0.5">0.5 кирпича (120 мм)</option>
                        <option value="1">1 кирпич (250 мм)</option>
                        <option value="1.5">1.5 кирпича (380 мм)</option>
                        <option value="2">2 кирпича (510 мм)</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">Запас (%)</span>
                    <input type="number" x-model.number="config.wasteFactor" class="mt-1 w-full bg-black/40 border-white/10 text-white rounded-lg">
                </label>
            </div>
        </div>

        <!-- Результаты -->
        <div class="w-full md:w-1/2 bg-black/30 rounded-2xl p-6 border border-white/5 relative overflow-hidden group">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-indigo-600/10 rounded-full blur-3xl group-hover:bg-indigo-600/20 transition-all duration-700"></div>
            
            <h4 class="text-lg font-bold text-white mb-6 italic border-b border-white/10 pb-2">Результат расчета (СНиП)</h4>
            
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-xs uppercase font-black tracking-tighter">Всего кирпича:</p>
                        <p class="text-3xl font-black text-indigo-400" x-text="results.brickCount + ' шт.'"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-400 text-xs uppercase font-black tracking-tighter">Объем кирпича:</p>
                        <p class="text-xl font-bold text-white" x-text="results.brickVolume + ' м³'"></p>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-white/10 pt-4">
                    <div>
                        <p class="text-slate-400 text-xs uppercase font-black tracking-tighter">Раствор (кладочная смесь):</p>
                        <p class="text-2xl font-black text-emerald-400" x-text="results.mortarVolume + ' м³'"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-400 text-xs uppercase font-black tracking-tighter">Вес стен (приб.):</p>
                        <p class="text-xl font-bold text-white" x-text="results.totalWeight + ' т'"></p>
                    </div>
                </div>

                <div class="bg-indigo-500/10 border border-indigo-500/30 p-3 rounded-lg mt-4 backdrop-blur-md">
                    <p class="text-[10px] text-indigo-300 italic">
                        * Данные рассчитаны по ГОСТ 530-2012. Учтены горизонтальные и вертикальные швы <span x-text="config.jointThickness"></span> мм, а также запас <span x-text="config.wasteFactor"></span>% на подрезку и бой.
                    </p>
                </div>

                <button class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-black italic tracking-widest transition-all shadow-lg shadow-indigo-600/40">
                    СОХРАНИТЬ В СМЕТУ
                </button>
            </div>
        </div>
    </div>
</div>
