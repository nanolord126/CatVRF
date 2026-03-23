@props(['template'])

<div x-data="flooringCalculator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Визуализация раскладки (Grid-based preview) -->
        <div class="w-full lg:w-1/2 bg-black/40 rounded-3xl min-h-[400px] border border-white/5 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[url('/img/grid.png')] opacity-20 pointer-events-none"></div>
            
            <div 
                class="relative border-4 border-white/10 shadow-2xl transition-all duration-500"
                :style="`width: ${config.width / 5}px; height: ${config.height / 5}px; background: ${config.material === 'laminate' ? '#78350f' : '#334155'}; grid-template-columns: repeat(${Math.ceil(config.width/200)}, 1fr); display: grid;`"
            >
                <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1 bg-white/10 rounded-full text-[10px] text-white italic font-bold" x-text="config.width + ' мм'"></div>
                <div class="absolute -right-20 top-1/2 -translate-y-1/2 px-3 py-1 bg-white/10 rounded-full text-[10px] text-white italic font-bold rotate-90" x-text="config.height + ' мм'"></div>

                <!-- Виртуальные плашки/плитки -->
                <template x-for="i in Math.floor((config.width * config.height) / (config.material === 'laminate' ? 120000 : 90000))">
                    <div 
                        class="border-white/5 border opacity-40 transition-all hover:opacity-100 cursor-help"
                        :class="config.material === 'laminate' ? 'border-amber-900 bg-amber-800' : 'border-slate-800 bg-slate-700'"
                    ></div>
                </template>
            </div>

            <div class="absolute top-6 left-6 flex space-x-2">
                <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-[10px] text-emerald-400 font-bold uppercase tracking-tight italic">S = <span x-text="results.area"></span> м²</span>
                <template x-if="config.is_heated">
                    <span class="px-3 py-1 bg-orange-500/10 border border-orange-500/20 rounded-full text-[10px] text-orange-400 font-bold italic">HEATED FLOOR</span>
                </template>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-1/2 flex flex-col space-y-6">
            <h2 class="text-3xl font-black text-white italic">Surface <span class="text-indigo-400">Master 2.0</span></h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-black pl-2 block mb-1 tracking-widest">Длина (мм)</span>
                        <input type="number" x-model.number="config.width" class="w-full bg-white/5 border border-white/10 text-white rounded-xl py-3 px-4 italic outline-none focus:ring-2 focus:ring-emerald-500">
                    </label>
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-black pl-2 block mb-1 tracking-widest">Ширина (мм)</span>
                        <input type="number" x-model.number="config.height" class="w-full bg-white/5 border border-white/10 text-white rounded-xl py-3 px-4 italic outline-none focus:ring-2 focus:ring-emerald-500">
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-black pl-2 block mb-1 tracking-widest">Материал</span>
                        <select x-model="config.material" class="w-full bg-white/5 border border-white/10 text-white rounded-xl py-3 px-4 outline-none">
                            <option value="laminate">Ламинат (Class 33)</option>
                            <option value="tiles">Керамогранит</option>
                            <option value="parquet">Паркетная доска</option>
                        </select>
                    </label>
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-black pl-2 block mb-1 tracking-widest">Запас на подрезку (%)</span>
                        <select x-model.number="config.waste" class="w-full bg-white/5 border border-white/10 text-white rounded-xl py-3 px-4 outline-none">
                            <option value="5">Прямая укладка (5%)</option>
                            <option value="10">Диагональная (10%)</option>
                            <option value="15">Елочка (15%)</option>
                        </select>
                    </label>
                </div>
                
                <!-- Toggle Переключатель -->
                <div class="flex items-center space-x-4 p-4 bg-white/5 rounded-2xl border border-white/10">
                    <div class="flex-grow">
                        <span class="text-white text-sm font-bold italic">Теплый пол</span>
                        <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter">Совместимость с нагревательными матами</p>
                    </div>
                    <button @click="config.is_heated = !config.is_heated" 
                        class="w-12 h-6 rounded-full transition-all duration-300 relative"
                        :class="config.is_heated ? 'bg-orange-600' : 'bg-slate-700'"
                    >
                        <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all duration-300"
                            :style="config.is_heated ? 'left: 27px' : 'left: 4px'"
                        ></div>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 bg-white/5 p-4 rounded-2xl border border-white/10">
                <div class="text-center p-3">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Расход материала</span>
                    <span class="text-xl font-bold text-white italic" x-text="results.packsQty + ' упак.'"></span>
                </div>
                <div class="text-center p-3 border-l border-white/10">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Суммарный вес</span>
                    <span class="text-xl font-bold text-white italic" x-text="results.weight + ' кг'"></span>
                </div>
            </div>

            <div class="flex justify-between items-end px-2">
               <div>
                   <span class="text-emerald-500 text-[10px] uppercase font-black tracking-widest block mb-1">ИТОГО К ОПЛАТЕ:</span>
                   <span class="text-4xl font-black text-white italic" x-text="formatPrice(totalPrice)"></span>
               </div>
               <button @click="saveFlooring()" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black italic shadow-lg shadow-emerald-900/40 transition-all active:scale-95">
                   В КОРЗИНУ (X-PRESS)
               </button>
            </div>
            
            <p class="text-[9px] text-slate-600 italic leading-relaxed uppercase tracking-tighter">Расчет носит информационный характер. Рекомендуем уточнить шаг укладки у мастера перед заказом. Погрешность расчета 1.5%.</p>
        </div>
    </div>
</div>
