@props(['template'])

<div x-data="saunaConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Сауны (Top-Down Thermal SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-orange-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(245,158,11,0.1)] transition-all duration-700">
                <!-- Outer Walls -->
                <rect x="50" y="50" width="400" height="400" fill="none" stroke="#78350f" stroke-width="15" rx="10" />
                
                <!-- Benches Architecture -->
                <rect x="65" y="65" width="200" height="60" fill="#a16207" rx="5" opacity="0.8" /> <!-- High level -->
                <rect x="65" y="125" width="250" height="60" fill="#a16207" rx="5" opacity="0.6" /> <!-- Mid level -->
                <rect x="65" y="400-65" width="250" height="60" fill="#a16207" rx="5" opacity="0.8" transform="rotate(270, 65, 335)" />

                <!-- Heater (Stove) -->
                <rect x="360" y="65" width="75" height="75" fill="#451a03" rx="5" />
                <template x-if="config.active">
                    <g>
                        <template x-for="i in 5">
                            <circle :cx="397" :cy="102" r="2" fill="#ef4444">
                                <animate attributeName="r" values="2;8;2" dur="2s" :begin="i * 0.4 + 's'" repeatCount="indefinite" />
                                <animate attributeName="opacity" values="0.8;0;0.8" dur="2s" :begin="i * 0.4 + 's'" repeatCount="indefinite" />
                            </circle>
                        </template>
                    </g>
                </template>

                <!-- Chromotherapy Lighting -->
                <template x-if="config.chromotherapy">
                    <rect x="65" y="65" width="2" height="370" :fill="config.lightColor" class="animate-pulse" />
                </template>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#f59e0b" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">TEMP: <span x-text="config.temp"></span> °C</text>
                <text x="320" y="480" fill="#f59e0b" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">CEDAR: PREMIUM</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'Humidity: ' + config.humidity + '%'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-orange-600 pb-4">
                Heat <span class="text-orange-500 text-6xl block mt-2">Sauna Pro</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Площадь (м²)</span>
                        <input type="number" x-model.number="config.area" min="2" max="25" step="1" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-orange-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Тип дерева</span>
                        <select x-model="config.wood" class="bg-transparent border-none text-white text-sm font-bold italic w-full focus:outline-none focus:text-orange-400 appearance-none uppercase tracking-widest">
                            <option value="Cedar" class="bg-slate-900 border-none">Canadian Red Cedar</option>
                            <option value="Hemlock" class="bg-slate-900 border-none">Hemlock Premium</option>
                            <option value="Abachi" class="bg-slate-900 border-none">Abachi (Cool Touch)</option>
                        </select>
                    </div>
                </div>

                <!-- Технология -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.tech = 'Traditional'" class="p-4 rounded-2xl border transition-all" :class="config.tech === 'Traditional' ? 'bg-orange-600/20 border-orange-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.tech === 'Traditional' ? 'text-orange-400' : 'text-slate-500'">Traditional Rock</span>
                        <span class="text-white text-xs font-bold italic">High Steam</span>
                    </button>
                    <button @click="config.tech = 'Infrared'" class="p-4 rounded-2xl border transition-all" :class="config.tech === 'Infrared' ? 'bg-orange-600/20 border-orange-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.tech === 'Infrared' ? 'text-orange-400' : 'text-slate-500'">Infrared Core</span>
                        <span class="text-white text-xs font-bold italic">Deep Tissue Heat</span>
                    </button>
                </div>

                <!-- Опции Wellness -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Chromotherapy RGB</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Light Flow Therapy</p>
                        </div>
                        <button @click="config.chromotherapy = !config.chromotherapy" class="w-12 h-6 rounded-full relative transition-all" :class="config.chromotherapy ? 'bg-orange-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.chromotherapy ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Salt Wall Inlay</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Himalayan Lonization</p>
                        </div>
                        <button @click="config.saltWall = !config.saltWall" class="w-12 h-6 rounded-full relative transition-all" :class="config.saltWall ? 'bg-orange-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.saltWall ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-orange-600/10 p-8 rounded-3xl border border-orange-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-orange-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Мощность печи (кВт)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.power + ' кВт'"></span>
                    </div>
                    <div class="border-l border-orange-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Нагрев до 90°C</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.heatingTime + ' мин'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Thermal Suite:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveSauna()" class="w-full py-6 bg-orange-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-orange-500 active:scale-95 transition-all shadow-2xl shadow-orange-500/20">
                СФОРМИРОВАТЬ SPA-ЗОНУ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет на основе оборудования Harvia / Tylo и древесины сорта Extra.</p>
        </div>
    </div>
</div>
