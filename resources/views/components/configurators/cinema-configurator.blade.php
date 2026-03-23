@props(['template'])

<div x-data="cinemaConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Кинотеатра (Top-Down Audio-Grid SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-red-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(239,68,68,0.1)] transition-all duration-700">
                <!-- Screen (Curved) -->
                <path d="M 50 50 Q 250 80 450 50" fill="none" stroke="#ef4444" stroke-width="8" opacity="0.8" />
                <path d="M 50 50 Q 250 80 450 50" fill="none" stroke="#ef4444" stroke-width="20" opacity="0.2" filter="blur(5px)" />

                <!-- Speaker Positions (7.2.4 Atmos) -->
                <g fill="#991b1b">
                    <circle cx="60" cy="100" r="10" /> <circle cx="440" cy="100" r="10" /> <!-- Front -->
                    <circle cx="250" cy="90" r="8" /> <!-- Center -->
                    <!-- Side / Ceiling -->
                    <template x-if="config.atmos">
                        <g>
                            <circle cx="100" cy="250" r="6" opacity="0.5" /> <circle cx="400" cy="250" r="6" opacity="0.5" />
                        </g>
                    </template>
                </g>

                <!-- Seating (Recliners) -->
                <template x-for="row in config.rows">
                    <g :transform="'translate(0, ' + (250 + row * 80) + ')'">
                        <template x-for="seat in row === 1 ? 4 : 5">
                            <rect :x="100 + (seat-1) * 70" y="0" width="60" height="50" fill="#1e1b4b" rx="8" />
                            <rect :x="100 + (seat-1) * 70 + 5" y="5" width="50" height="30" fill="#ef4444" opacity="0.4" rx="4" />
                        </template>
                    </g>
                </template>

                <!-- Acoustic Panels -->
                <template x-for="i in 6">
                    <rect x="10" :y="i * 80" width="5" height="40" fill="#450a0a" rx="2" />
                    <rect x="485" :y="i * 80" width="5" height="40" fill="#450a0a" rx="2" />
                </template>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#fca5a5" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">SCREEN: 180" 4K HDR</text>
                <text x="320" y="480" fill="#fca5a5" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">AUDIO: <span x-text="config.audioSystem"></span></text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'Isolation: ' + config.isolation + ' dB'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-red-600 pb-4">
                Cinema <span class="text-red-500 text-6xl block mt-2">Empire Pro</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Кол-во рядов</span>
                        <input type="number" x-model.number="config.rows" min="1" max="4" step="1" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-red-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Проектор</span>
                        <select x-model="config.projector" class="bg-transparent border-none text-white text-sm font-bold italic w-full focus:outline-none focus:text-red-400 appearance-none uppercase tracking-widest">
                            <option value="Laser" class="bg-slate-900 border-none">JVC Laser 8K</option>
                            <option value="Sony" class="bg-slate-900 border-none">Sony SXRD 4K</option>
                            <option value="Epson" class="bg-slate-900 border-none">Epson Pro Home</option>
                        </select>
                    </div>
                </div>

                <!-- Технология звука -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.audioSystem = '7.2.4 Atmos'" class="p-4 rounded-2xl border transition-all" :class="config.audioSystem === '7.2.4 Atmos' ? 'bg-red-600/20 border-red-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.audioSystem === '7.2.4 Atmos' ? 'text-red-400' : 'text-slate-500'">Dolby Atmos Elite</span>
                        <span class="text-white text-xs font-bold italic">Immersion Level 10</span>
                    </button>
                    <button @click="config.audioSystem = '5.1 Classic'" class="p-4 rounded-2xl border transition-all" :class="config.audioSystem === '5.1 Classic' ? 'bg-red-600/20 border-red-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.audioSystem === '5.1 Classic' ? 'text-red-400' : 'text-slate-500'">Surround Classic</span>
                        <span class="text-white text-xs font-bold italic">Standard Cinema</span>
                    </button>
                </div>

                <!-- Опции Комфорта -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Motorized Recliners</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Zero Gravity Mode</p>
                        </div>
                        <button @click="config.recliners = !config.recliners" class="w-12 h-6 rounded-full relative transition-all" :class="config.recliners ? 'bg-red-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.recliners ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Starry Ceiling Sky</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Optic Fiber Galaxy</p>
                        </div>
                        <button @click="config.starrySky = !config.starrySky" class="w-12 h-6 rounded-full relative transition-all" :class="config.starrySky ? 'bg-red-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.starrySky ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-red-600/10 p-8 rounded-3xl border border-red-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-red-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Яркость (Lumens)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.lumens + ' lm'"></span>
                    </div>
                    <div class="border-l border-red-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Акустика</span>
                        <span class="text-2xl font-black text-white italic">Klipsh / Bowers</span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Cinema Private:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveCinema()" class="w-full py-6 bg-red-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-red-500 active:scale-95 transition-all shadow-2xl shadow-red-500/20">
                СФОРМИРОВАТЬ ТЕАТР
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет на базе THX-сертифицированного оборудования и профессиональной акустической подготовки Vicoustic.</p>
        </div>
    </div>
</div>
