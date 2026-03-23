@props(['template'])

<div x-data="terraceConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Террасы (Decking SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-amber-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(245,158,11,0.1)] transition-all duration-700">
                <!-- Deck Surface -->
                <rect x="50" y="50" width="400" height="400" fill="none" stroke="#d97706" stroke-width="1" rx="5" />
                
                <!-- Deck Planks -->
                <template x-for="i in 20">
                    <rect x="55" :y="55 + (i-1)*20" width="390" height="15" :fill="config.deckColor === 'Teak' ? '#92400e' : '#451a03'" opacity="0.8" rx="2" />
                </template>

                <!-- Pergola Beams -->
                <template x-if="config.pergola">
                    <g>
                        <template x-for="i in 5">
                            <rect :x="100 + (i-1)*80" y="20" width="10" height="460" fill="#78350f" rx="2" />
                        </template>
                        <template x-for="i in 4">
                            <rect x="20" :y="100 + (i-1)*100" width="460" height="8" fill="#78350f" rx="1" />
                        </template>
                    </g>
                </template>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#f59e0b" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">AREA: <span x-text="config.area"></span> m²</text>
                <text x="320" y="480" fill="#f59e0b" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">WPC: 3D Embossed</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'Material: ' + config.material"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-amber-600 pb-4">
                Deck <span class="text-amber-500 text-6xl block mt-2">Space Pro</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Площадь (м²)</span>
                        <input type="number" x-model.number="config.area" min="10" max="200" step="5" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-amber-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Цвет декинга</span>
                        <select x-model="config.deckColor" class="bg-transparent border-none text-white text-sm font-bold italic w-full focus:outline-none focus:text-amber-400 appearance-none uppercase tracking-widest">
                            <option value="Teak" class="bg-slate-900 border-none">Classic Teak</option>
                            <option value="Wenge" class="bg-slate-900 border-none">Dark Wenge</option>
                            <option value="Grey" class="bg-slate-900 border-none">Modern Grey</option>
                        </select>
                    </div>
                </div>

                <!-- Выбор материала -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.material = 'WPC'" class="p-4 rounded-2xl border transition-all" :class="config.material === 'WPC' ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.material === 'WPC' ? 'text-amber-400' : 'text-slate-500'">WPC Composite</span>
                        <span class="text-white text-xs font-bold italic">Zero Maintenance</span>
                    </button>
                    <button @click="config.material = 'Larch'" class="p-4 rounded-2xl border transition-all" :class="config.material === 'Larch' ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.material === 'Larch' ? 'text-amber-400' : 'text-slate-500'">Siberian Larch</span>
                        <span class="text-white text-xs font-bold italic">Natural Wood Feel</span>
                    </button>
                </div>

                <!-- Опции -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Bio-Climatic Pergola</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Adjustable Lamellas</p>
                        </div>
                        <button @click="config.pergola = !config.pergola" class="w-12 h-6 rounded-full relative transition-all" :class="config.pergola ? 'bg-amber-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.pergola ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Smart Step Lighting</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Motion Sensor Link</p>
                        </div>
                        <button @click="config.stepLights = !config.stepLights" class="w-12 h-6 rounded-full relative transition-all" :class="config.stepLights ? 'bg-amber-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.stepLights ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-amber-600/10 p-8 rounded-3xl border border-amber-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-amber-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Метраж доски (м.п.)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.boardLinearLength + ' м.п.'"></span>
                    </div>
                    <div class="border-l border-amber-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Опорная база</span>
                        <span class="text-2xl font-black text-white italic">Adjustable Pedestals</span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Garden Deck:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveTerrace()" class="w-full py-6 bg-amber-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-amber-500 active:scale-95 transition-all shadow-2xl shadow-amber-500/20">
                СФОРМИРОВАТЬ ТЕРРАСУ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет на основе WPC-Premium (Wood Polymer Composite) и опорных систем Buzon.</p>
        </div>
    </div>
</div>
