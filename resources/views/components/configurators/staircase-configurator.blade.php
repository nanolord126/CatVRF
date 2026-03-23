@props(['template'])

<div x-data="stairsConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- 2D/3D Схема (SVG/Canvas) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[500px] border border-white/5 p-12 relative flex items-center justify-center overflow-hidden">
            <template x-if="config.type === 'straight'">
                <h2 class="text-white text-3xl font-black italic absolute top-10 right-10 opacity-10">Straight Stair</h2>
            </template>
            <template x-if="config.type === 'spiral'">
                <h2 class="text-white text-3xl font-black italic absolute top-10 right-10 opacity-10">Spiral Stair</h2>
            </template>

            <svg viewBox="0 0 400 600" class="w-full max-h-[500px] drop-shadow-[0_0_20px_#6366f1] transition-transform duration-700"
                :class="{'rotate-45': config.type === 'spiral'}">
                <!-- Каждая ступень (отрисовка) -->
                <template x-for="i in config.stepCount">
                    <rect 
                        :x="50 + (config.type === 'straight' ? (i * 15) : 0)" 
                        :y="550 - (i * (500 / config.stepCount))" 
                        :width="config.type === 'straight' ? 250 : 200" 
                        height="10" 
                        fill="#6366f1" 
                        class="opacity-90 stroke-white/20 stroke-1"
                        :style="`opacity: ${0.3 + (i / config.stepCount)}; transform-origin: top left; transform: ${config.type === 'spiral' ? 'rotate('+(i*20)+'deg)' : ''}`"
                    />
                </template>
                
                <!-- Тетива / Косоур -->
                <line x1="50" y1="550" :x2="50 + (config.type === 'straight' ? config.stepCount * 15 : 0)" :y2="550 - (config.stepCount * (500/config.stepCount))" class="stroke-indigo-400 stroke-[5]" />
            </svg>

            <!-- Технические замеры на лету -->
            <div class="absolute bottom-8 left-8 flex flex-col gap-2">
                <div class="px-3 py-1 bg-black/50 border border-white/10 rounded-full text-[10px] text-indigo-400 font-black italic">H_RISER: <span x-text="results.stepHeight"></span> мм</div>
                <div class="px-3 py-1 bg-black/50 border border-white/10 rounded-full text-[10px] text-indigo-400 font-black italic">W_TREAD: <span x-text="results.stepWidth"></span> мм</div>
                <div class="px-3 py-1 bg-black/50 border border-white/10 rounded-full text-[10px] text-emerald-400 font-black italic">ANGLE: <span x-text="results.angle"></span>°</div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8 py-4">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-indigo-500 pb-4">Engineering <span class="text-indigo-500">Staircase 2.0</span></h1>
            
            <div class="grid grid-cols-2 gap-4">
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Тип лестницы</span>
                    <select x-model="config.type" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all outline-none">
                        <option value="straight">Прямая маршевая</option>
                        <option value="l_shape">Поворотная (Г-образная)</option>
                        <option value="spiral">Винтовая (Premium)</option>
                    </select>
                </label>
                <label>
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Материал каркаса</span>
                    <select x-model="config.frameMaterial" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 transition-all outline-none">
                        <option value="wood">Дуб / Бук (Массив)</option>
                        <option value="metal">Металлокаркас (Loft)</option>
                        <option value="reinforced">Бетон (Cast)</option>
                    </select>
                </label>
            </div>

            <div class="space-y-6">
                <div>
                    <div class="flex justify-between mb-2 px-2">
                        <span class="text-slate-400 text-xs italic font-bold uppercase tracking-tight">Высота проема (H):</span>
                        <span class="text-white font-black" x-text="config.height + ' мм'"></span>
                    </div>
                    <input type="range" x-model.number="config.height" min="2000" max="4500" step="10" class="w-full h-1.5 bg-white/10 rounded-full appearance-none accent-indigo-500 cursor-pointer">
                </div>

                <div>
                    <div class="flex justify-between mb-2 px-2">
                        <span class="text-slate-400 text-xs italic font-bold uppercase tracking-tight">Количество ступеней:</span>
                        <span class="text-white font-black" x-text="config.stepCount"></span>
                    </div>
                    <input type="range" x-model.number="config.stepCount" min="3" max="25" class="w-full h-1.5 bg-white/10 rounded-full appearance-none accent-indigo-500 cursor-pointer">
                </div>
            </div>

            <div class="bg-indigo-600/10 p-6 rounded-3xl border border-indigo-500/20 space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-300 italic">Ступени / Перила:</span>
                    <span class="text-white font-bold" x-text="config.frameMaterial === 'wood' ? 'Premium Wood' : 'High Performance Steel'"></span>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic">Смета проекта:</span>
                    <span class="text-3xl font-black text-white hover:text-emerald-400 transition-colors cursor-default" x-text="formatPrice(totalPrice)"></span>
                </div>
                <p class="text-[9px] text-slate-500 italic uppercase tracking-tighter leading-relaxed">Расчет произведен по СНиП 21-01-97. Учтены безопасный угол наклона и критическая высота проема.</p>
            </div>

            <button @click="saveStairs()" class="w-full py-5 bg-white text-slate-900 rounded-3xl font-black italic tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-white/10 border-b-4 border-indigo-200">
                СФОРМИРОВАТЬ ЧЕРТЕЖИ (.DWG / .PDF)
            </button>
        </div>
    </div>
</div>
