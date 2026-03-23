@props(['template'])

<div x-data="lightingConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Освещения (Lux Map SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-yellow-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(250,204,21,0.1)] transition-all duration-700">
                <!-- Room Outline -->
                <rect x="50" y="50" width="400" height="400" fill="none" stroke="#facc15" stroke-width="2" rx="10" opacity="0.3" />
                
                <!-- Smart Track Rail -->
                <template x-if="config.system === 'Track'">
                    <path d="M 100 100 L 400 100 L 400 400" stroke="#451a03" stroke-width="8" fill="none" stroke-linecap="round" />
                </template>

                <!-- Light Sources (Spots/Diffusers) -->
                <template x-for="i in config.fixtures">
                    <g :transform="'translate(' + (100 + (i-1)*60 % 300) + ',' + (100 + Math.floor((i-1)*60 / 300)*100) + ')'">
                        <!-- Glow Effect -->
                        <circle cx="0" cy="0" :r="config.intensity / 2" :fill="config.color" opacity="0.15">
                            <animate attributeName="r" :values="(config.intensity/3) + ';' + (config.intensity/2) + ';' + (config.intensity/3)" dur="3s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="0" cy="0" r="10" :fill="config.color" opacity="0.8" />
                        <path d="M -5 -5 L 5 5 M -5 5 L 5 -5" stroke="white" stroke-width="1" />
                    </g>
                </template>

                <!-- Hidden Backlight (Cove) -->
                <template x-if="config.backlight">
                    <rect x="55" y="55" width="390" height="390" fill="none" :stroke="config.color" stroke-width="20" opacity="0.1" rx="5" />
                </template>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#facc15" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">LUX: <span x-text="results.lux"></span> lx</text>
                <text x="320" y="480" fill="#facc15" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">PROTOCOL: <span x-text="config.protocol"></span></text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full" :style="'background-color: ' + config.color"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'CRI: ' + config.cri + ' Ra'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-yellow-600 pb-4">
                Smart <span class="text-yellow-500 text-6xl block mt-2">Lumen AI</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Кол-во точек</span>
                        <input type="number" x-model.number="config.fixtures" min="4" max="40" step="2" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-yellow-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Протокол управления</span>
                        <select x-model="config.protocol" class="bg-transparent border-none text-white text-sm font-bold italic w-full focus:outline-none focus:text-yellow-400 appearance-none uppercase tracking-widest">
                            <option value="DALI" class="bg-slate-900 border-none">DALI-2 Protocol</option>
                            <option value="KNX" class="bg-slate-900 border-none">KNX Home Auto</option>
                            <option value="Zigbee" class="bg-slate-900 border-none">Zigbee 3.0 Wireless</option>
                            <option value="Casambi" class="bg-slate-900 border-none">Casambi Mesh</option>
                        </select>
                    </div>
                </div>

                <!-- Температура и Цвет -->
                <div class="space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Цветовая температура (K)</span>
                    <input type="range" x-model.number="config.temp" min="1800" max="6500" class="w-full h-1 bg-yellow-900/40 rounded-lg appearance-none cursor-pointer accent-yellow-500">
                    <div class="flex justify-between text-[10px] text-white italic font-bold">
                        <span>Candle (1800K)</span>
                        <span x-text="config.temp + 'K'"></span>
                        <span>Daylight (6500K)</span>
                    </div>
                </div>

                <!-- Система инсталляции -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.system = 'Track'" class="p-4 rounded-2xl border transition-all" :class="config.system === 'Track' ? 'bg-yellow-600/20 border-yellow-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.system === 'Track' ? 'text-yellow-400' : 'text-slate-500'">Magnetic Track</span>
                        <span class="text-white text-xs font-bold italic">Modular Tech</span>
                    </button>
                    <button @click="config.system = 'Recessed'" class="p-4 rounded-2xl border transition-all" :class="config.system === 'Recessed' ? 'bg-yellow-600/20 border-yellow-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.system === 'Recessed' ? 'text-yellow-400' : 'text-slate-500'">Seamless Recessed</span>
                        <span class="text-white text-xs font-bold italic">Minimal Look</span>
                    </button>
                </div>

                <!-- Режимы AI -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Biodynamic Circadian</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Sun Cycle Sync</p>
                        </div>
                        <button @click="config.circadian = !config.circadian" class="w-12 h-6 rounded-full relative transition-all" :class="config.circadian ? 'bg-yellow-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.circadian ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-yellow-600/10 p-8 rounded-3xl border border-yellow-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-yellow-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Потребление (Вт)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.consumption + ' W'"></span>
                    </div>
                    <div class="border-l border-yellow-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Срок службы</span>
                        <span class="text-2xl font-black text-white italic">50,000 hrs</span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Photon Core:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveLighting()" class="w-full py-6 bg-yellow-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-yellow-500 active:scale-95 transition-all shadow-2xl shadow-yellow-500/20">
                СФОРМИРОВАТЬ СВЕТ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет на базе чипов Osram/Bridgelux и драйверов MeanWell/Tridonic. Соответствует DIN EN 12464-1.</p>
        </div>
    </div>
</div>
