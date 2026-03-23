@props(['template'])

<div x-data="verticalGardenConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Сада (Bio-Dynamic SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(16,185,129,0.03),transparent)] animate-pulse"></div>
            
            <svg viewBox="0 0 500 600" class="w-full max-h-[450px] drop-shadow-[0_20px_40px_rgba(16,185,129,0.1)] transition-all duration-700">
                <!-- Frame Structure -->
                <rect x="50" y="50" width="400" height="500" fill="none" stroke="#064e3b" stroke-width="4" rx="10" />
                
                <!-- Planting Modules -->
                <template x-for="row in config.rows" :key="row">
                    <g :transform="'translate(60, ' + (60 + (row-1)*60) + ')'">
                        <template x-for="col in config.cols" :key="col">
                            <g :transform="'translate(' + ((col-1)*50) + ', 0)'">
                                <path d="M 0 40 Q 20 60 40 40" fill="#1e293b" stroke="#334155" />
                                <!-- Dynamic Leaves -->
                                <template x-for="leaf in 3">
                                    <path d="M 15 35 Q 20 10 25 35" :fill="config.health > 80 ? '#10b981' : '#fbbf24'" opacity="0.8">
                                        <animateTransform attributeName="transform" type="rotate" :values="'-5 20 35; 5 20 35; -5 20 35'" :dur="2 + Math.random() * 2 + 's'" repeatCount="indefinite" />
                                    </path>
                                </template>
                            </g>
                        </template>
                    </g>
                </template>

                <!-- Hydroponic Flow -->
                <template x-if="config.watering">
                    <g stroke="#3b82f6" stroke-width="2" fill="none" opacity="0.6">
                        <path d="M 450 50 L 450 550" />
                        <template x-for="row in config.rows">
                            <path :d="'M 450 ' + (80 + (row-1)*60) + ' L 50 ' + (80 + (row-1)*60)">
                                <animate attributeName="stroke-dasharray" values="20,10; 10,20; 20,10" dur="2s" repeatCount="indefinite" />
                            </path>
                        </template>
                    </g>
                </template>

                <!-- Annotations -->
                <text x="60" y="30" fill="#10b981" font-size="10" font-weight="bold" font-family="monospace">OXYGEN YIELD: +<span x-text="config.o2"></span> m³/h</text>
                <text x="320" y="30" fill="#a78bfa" font-size="10" font-weight="bold" font-family="monospace">PAR LIGHT: <span x-text="config.par"></span> µmol</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4 uppercase font-black italic text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-emerald-600/20 border border-emerald-500/50 rounded-2xl flex items-center space-x-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-white" x-text="'Eco-State: ' + config.ecoStatus"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-emerald-600 pb-4">
                Vertical <span class="text-emerald-500 text-6xl block mt-2">Bio Core</span>
            </h1>

            <div class="space-y-6">
                <!-- Конфигурация Размера -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Высота (м)</span>
                        <input type="number" x-model="config.height" step="0.5" min="1" max="4" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-emerald-400">
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Ширина (м)</span>
                        <input type="number" x-model="config.width" step="0.5" min="1" max="6" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-emerald-400 text-right">
                    </div>
                </div>

                <!-- Выбор Флоры -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none text-center italic">Тип Экосистемы</span>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="config.flora = 'moss'" class="p-3 rounded-xl border flex flex-col transition-all text-center" :class="config.flora === 'moss' ? 'bg-emerald-600/20 border-emerald-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-[8px] leading-none">MOSS</span>
                            <span class="text-[6px] text-slate-500 font-extrabold mt-1">Stabilized</span>
                        </button>
                        <button @click="config.flora = 'tropical'" class="p-3 rounded-xl border flex flex-col transition-all text-center" :class="config.flora === 'tropical' ? 'bg-emerald-600/20 border-emerald-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-[8px] leading-none">TROPIC</span>
                            <span class="text-[6px] text-slate-500 font-extrabold mt-1">Living</span>
                        </button>
                        <button @click="config.flora = 'herbs'" class="p-3 rounded-xl border flex flex-col transition-all text-center" :class="config.flora === 'herbs' ? 'bg-emerald-600/20 border-emerald-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-[8px] leading-none">FOOD</span>
                            <span class="text-[6px] text-slate-500 font-extrabold mt-1">Hydroponic</span>
                        </button>
                    </div>
                </div>

                <!-- Автоматизация -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-black italic block leading-none">AI Nutrient Flow</span>
                            <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1">Auto Fertilization</p>
                        </div>
                        <button @click="config.autoWater = !config.autoWater" class="w-10 h-5 rounded-full relative transition-all" :class="config.autoWater ? 'bg-emerald-600 shadow-[0_0_8px_rgba(16,185,129,0.4)]' : 'bg-slate-700'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all" :style="config.autoWater ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-black italic block leading-none">Bio-Spectral Lighting</span>
                            <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1">Sun-Sync Spectrum</p>
                        </div>
                        <button @click="config.smartLight = !config.smartLight" class="w-10 h-5 rounded-full relative transition-all" :class="config.smartLight ? 'bg-emerald-600 shadow-[0_0_8px_rgba(16,185,129,0.4)]' : 'bg-slate-700'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all" :style="config.smartLight ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-emerald-600/10 p-8 rounded-3xl border border-emerald-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-emerald-500/10 uppercase font-black italic tracking-widest">
                    <div>
                        <span class="text-slate-500 text-[9px] block mb-1">Полив (л/мес)</span>
                        <span class="text-2xl text-white leading-none">42 <span class="text-xs">L</span></span>
                    </div>
                    <div class="border-l border-emerald-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] block mb-1">Коэф. Humidity</span>
                        <span class="text-2xl text-white leading-none">+15 <span class="text-xs">%</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Phyto Wall Pro 2026:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="savePhytoSystem()" class="w-full py-6 bg-emerald-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-emerald-500 active:scale-95 transition-all shadow-2xl shadow-emerald-500/20 uppercase leading-none">
                ОЗЕЛЕНИТЬ ПРОСТРАНСТВО
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold leading-none">Включает систему замкнутого цикла и комплект стартовой флоры. Монтаж за 6 часов.</p>
        </div>
    </div>
</div>
