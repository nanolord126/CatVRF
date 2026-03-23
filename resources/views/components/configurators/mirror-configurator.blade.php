@props(['template'])

<div x-data="mirrorConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Зеркала (Perspective Reflective SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent skew-x-12 opacity-30"></div>
            
            <svg viewBox="0 0 500 600" class="w-full max-h-[450px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Wall Shadow -->
                <rect x="70" y="70" width="380" height="480" fill="rgba(0,0,0,0.4)" rx="10" />
                
                <!-- Mirror Glass Shell -->
                <rect x="60" y="60" width="380" height="480" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.2)" stroke-width="1" rx="10" />
                
                <!-- Display Area (TV Interface) -->
                <template x-if="config.on">
                    <g transform="translate(90, 150)">
                        <rect width="320" height="180" fill="rgba(56,189,248,0.1)" stroke="rgba(56,189,248,0.2)" rx="5" />
                        <text x="160" y="90" text-anchor="middle" fill="#38bdf8" font-size="20" font-weight="black" font-family="monospace italic" opacity="0.6 uppercase leading-none">Smart Mirror OS</text>
                        <template x-for="line in 3">
                            <rect x="20" :y="110 + line*15" width="280" height="2" fill="#38bdf8" opacity="0.2">
                                <animate attributeName="width" values="0;280;0" :dur="1+line*0.5+'s'" repeatCount="indefinite" />
                            </rect>
                        </template>
                    </g>
                </template>

                <!-- Backlighting (Ambilight) -->
                <template x-if="config.backlight">
                    <rect x="55" y="55" width="390" height="490" fill="none" :stroke="config.lightColor" stroke-width="6" rx="12" opacity="0.4" class="blur-md animate-pulse" />
                </template>

                <!-- Frame -->
                <rect x="50" y="50" width="400" height="500" fill="none" stroke="#e2e8f0" stroke-width="10" rx="15" x-show="config.frame" />

                <!-- Annotations -->
                <text x="60" y="40" fill="#cbd5e1" font-size="10" font-weight="bold" font-family="monospace">RESOLUTION: <span x-text="config.resolution"></span>K</text>
                <text x="320" y="40" fill="#cbd5e1" font-size="10" font-weight="bold" font-family="monospace">OPACITY: <span x-text="config.transmission"></span>%</text>
            </svg>

            <!-- Status Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4 uppercase font-black italic text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="config.on ? 'bg-sky-500 animate-pulse' : 'bg-slate-700'"></div>
                    <span class="text-white" x-text="config.on ? 'TV MODE: ON' : 'TV MODE: HIDDEN'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-slate-400 pb-4">
                Titan <span class="text-slate-400 text-6xl block mt-2">Mirror TV</span>
            </h1>

            <div class="space-y-6">
                <!-- Размеры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Высота (см)</span>
                        <input type="number" x-model="config.height" step="10" min="60" max="220" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-slate-400">
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Ширина (см)</span>
                        <input type="number" x-model="config.width" step="10" min="60" max="220" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-slate-400 text-right">
                    </div>
                </div>

                <!-- ТВ Интеграция -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-5">
                    <div class="flex justify-between items-end">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Диагональ ТВ (дюймы)</span>
                        <span class="text-white font-black italic leading-none" x-text="config.tvSize + ''''"></span>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="size in [32, 43, 55, 65]">
                            <button @click="config.tvSize = size" class="p-2 rounded-lg border text-[10px] font-black italic transition-all" :class="config.tvSize === size ? 'bg-sky-600/20 border-sky-500 text-white' : 'bg-white/5 border-white/10 text-slate-500'" x-text="size + ''''"></button>
                        </template>
                    </div>
                </div>

                <!-- Кастомизация -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-black italic block leading-none leading-none">Ambilight Multi-Color</span>
                            <p class="text-[8px] text-slate-500 uppercase font-bold tracking-widest mt-1">16.8M Colors</p>
                        </div>
                        <button @click="config.backlight = !config.backlight" class="w-10 h-5 rounded-full relative transition-all" :class="config.backlight ? 'bg-sky-600 shadow-[0_0_8px_rgba(56,189,248,0.4)]' : 'bg-slate-700'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all" :style="config.backlight ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-black italic block leading-none leading-none">Mirror Edge Grinding</span>
                            <p class="text-[8px] text-slate-500 uppercase font-bold tracking-widest mt-1">Classic Bevel 25mm</p>
                        </div>
                        <button @click="config.bevel = !config.bevel" class="w-10 h-5 rounded-full relative transition-all" :class="config.bevel ? 'bg-sky-600 shadow-[0_0_8px_rgba(56,189,248,0.4)]' : 'bg-slate-700'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all" :style="config.bevel ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-slate-600/10 p-8 rounded-3xl border border-slate-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-500/10 uppercase font-black italic tracking-widest leading-none">
                    <div>
                        <span class="text-slate-500 text-[9px] block mb-1">Светопропускание</span>
                        <span class="text-2xl text-white leading-none">75 <span class="text-xs">%</span></span>
                    </div>
                    <div class="border-l border-slate-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] block mb-1">Толщина стекла</span>
                        <span class="text-2xl text-white leading-none">6 <span class="text-xs">mm</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Titan Glass Ultra 4K:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveMirrorTV()" class="w-full py-6 bg-slate-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-slate-500 active:scale-95 transition-all shadow-2xl shadow-slate-500/20 uppercase leading-none">
                ОФОРМИТЬ В ИНТЕРЬЕРЕ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold leading-none">Премиальное закаленное стекло AGC. Подходит для влажных помещений. IP54 Protection.</p>
        </div>
    </div>
</div>
