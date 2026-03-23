@props(['template'])

<div x-data="doorConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация двери (SVG 2D rendering) -->
        <div class="w-full lg:w-1/2 bg-black/60 rounded-3xl min-h-[500px] border border-white/5 relative flex items-center justify-center overflow-hidden">
            <div 
                class="relative border-4 border-white/10 shadow-black shadow-2xl transition-all duration-700 h-[450px]"
                :style="`width: ${config.width / 5}px; background: ${config.texture === 'oak' ? '#451a03' : config.texture === 'wenge' ? '#171717' : '#fafafa'};`"
            >
                <!-- Полотно -->
                <div class="absolute inset-2 border border-white/10 shadow-inner group-hover:scale-105 transition-all duration-300"></div>
                
                <!-- Остекление -->
                <template x-if="config.hasGlass">
                    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-1/3 h-1/3 bg-blue-400/20 backdrop-blur-md border border-white/30 flex items-center justify-center">
                        <div class="w-full h-px bg-white/20 rotate-45"></div>
                        <div class="w-full h-px bg-white/20 -rotate-45"></div>
                    </div>
                </template>

                <!-- Ручка -->
                <div 
                    class="absolute top-1/2 w-8 h-2 bg-gradient-to-r transition-all duration-500"
                    :class="config.hardware === 'black' ? 'from-black to-slate-900' : 'from-amber-400 to-amber-600'"
                    :style="config.opening === 'right' ? 'right: 5px' : 'left: 5px'"
                ></div>

                <!-- Подпись размеров -->
                <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1 bg-white/5 rounded-lg text-[10px] text-white font-bold italic border border-white/10" x-text="config.width + ' мм'"></div>
                <div class="absolute -right-24 top-1/2 -translate-y-1/2 px-3 py-1 bg-white/5 rounded-lg text-[10px] text-white font-bold italic border border-white/10 rotate-90" x-text="config.height + ' мм'"></div>
            </div>

            <!-- Эффект освещения -->
            <div class="absolute top-10 right-10 flex space-x-3">
                <span class="p-2 rounded-full bg-white/10 border border-white/20 animate-pulse"></span>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-1/2 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-indigo-500 pb-4">
                Door <span class="text-indigo-500">Guardian 2.0</span>
            </h1>

            <div class="grid grid-cols-2 gap-4">
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Покрытие</span>
                    <select x-model="config.texture" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none">
                        <option value="oak">Дуб Натуральный</option>
                        <option value="wenge">Венге (Black)</option>
                        <option value="white">Эмаль Белая</option>
                    </select>
                </label>
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Фурнитура</span>
                    <select x-model="config.hardware" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none">
                        <option value="gold">Золото (Premium)</option>
                        <option value="black">Черный матовый (Loft)</option>
                        <option value="chrome">Хром (Modern)</option>
                    </select>
                </label>
            </div>

            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        @click="config.opening = 'left'"
                        class="py-3 px-4 rounded-2xl border-2 transition-all font-bold italic text-sm"
                        :class="config.opening === 'left' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-slate-500 hover:border-white/10'"
                    >Открывание: ЛЕВОЕ</button>
                    <button 
                        @click="config.opening = 'right'"
                        class="py-3 px-4 rounded-2xl border-2 transition-all font-bold italic text-sm"
                        :class="config.opening === 'right' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-slate-500 hover:border-white/10'"
                    >Открывание: ПРАВОЕ</button>
                </div>

                <div class="flex items-center space-x-4 p-4 bg-white/5 rounded-2xl border border-white/10">
                    <div class="flex-grow">
                        <span class="text-white text-sm font-bold italic leading-none block">Стеклянная вставка</span>
                        <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Закаленное стекло 8мм с матированием</p>
                    </div>
                    <button @click="config.hasGlass = !config.hasGlass" 
                        class="w-12 h-6 rounded-full transition-all duration-300 relative"
                        :class="config.hasGlass ? 'bg-indigo-600' : 'bg-slate-700'"
                    >
                        <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all duration-300"
                            :style="config.hasGlass ? 'left: 27px' : 'left: 4px'"
                        ></div>
                    </button>
                </div>
            </div>

            <div class="bg-indigo-600/10 p-6 rounded-3xl border border-indigo-500/20 space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-300 italic">Коробка / Наличники:</span>
                    <span class="text-white font-bold italic">Telescopic System Included</span>
                </div>
                <div class="flex justify-between items-end pt-2">
                    <span class="text-slate-400 italic uppercase text-[10px] font-black">Комплект под ключ:</span>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 block italic line-through" x-text="formatPrice(totalPrice * 1.2)"></span>
                        <span class="text-3xl font-black text-white italic" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
            </div>

            <button @click="saveDoor()" class="w-full py-5 bg-white text-slate-900 rounded-3xl font-black italic tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-white/5">
                ЗАКАЗАТЬ ВЫЕЗД ЗАМЕРЩИКА (FREE)
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Бесплатный выезд возможен в радиусе 100 км от распределительного центра. Гарантия на полотно 15 лет.</p>
        </div>
    </div>
</div>
