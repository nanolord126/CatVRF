@props(['template'])

<div x-data="aquariumConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Аквариума (Fish Tank SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-blue-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(59,130,246,0.15)] transition-all duration-700">
                <!-- Tank Structure (3Dish Perspective) -->
                <rect x="50" y="50" width="400" height="300" fill="#1e3a8a" opacity="0.4" stroke="#60a5fa" stroke-width="2" rx="10" />
                <path d="M 50 50 L 80 20 L 430 20 L 400 50 Z" fill="#1e40af" opacity="0.6" stroke="#60a5fa" stroke-width="2" />
                
                <!-- Water Line (Animated Bubbles) -->
                <rect x="55" y="60" width="390" height="280" fill="#3b82f6" opacity="0.2" rx="5" />
                
                <!-- Dynamic Elements: Fish/Bubbles -->
                <template x-for="i in config.fishCount">
                    <circle :cx="80 + (i*15)%300" :cy="100 + (Math.sin(i)*50 + 150)" r="4" fill="#fb923c" class="animate-bounce" />
                </template>

                <template x-for="i in 10">
                    <circle :cx="100 + (i*35)" :cy="340" r="2" fill="white" opacity="0.5">
                        <animate attributeName="cy" from="340" to="60" dur="2s" :begin="i*0.2 + 's'" repeatCount="indefinite" />
                    </circle>
                </template>

                <!-- Ground/Plants -->
                <rect x="50" y="320" width="400" height="30" fill="#451a03" opacity="0.8" rx="5" />
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#60a5fa" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">VOLUME: <span x-text="config.volume"></span> Liters</text>
                <text x="320" y="480" fill="#60a5fa" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">REEF: <span x-text="config.reefType"></span> Ecosystem</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'Automation: ' + (config.automation ? 'Full' : 'Basic')"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-blue-500 pb-4">
                Ocean <span class="text-blue-500 text-6xl block mt-2">Marine Cube</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Объем (Литры)</span>
                        <input type="number" x-model.number="config.volume" min="50" max="2000" step="50" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-blue-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Кол-во рыб (Ед.)</span>
                        <input type="number" x-model.number="config.fishCount" min="2" max="50" step="2" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-blue-400">
                    </div>
                </div>

                <!-- Тип экосистемы -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.reefType = 'Marine'" class="p-4 rounded-2xl border transition-all" :class="config.reefType === 'Marine' ? 'bg-blue-600/20 border-blue-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.reefType === 'Marine' ? 'text-blue-400' : 'text-slate-500'">Marine Reef</span>
                        <span class="text-white text-xs font-bold italic">Coral Ecosystem</span>
                    </button>
                    <button @click="config.reefType = 'Fresh'" class="p-4 rounded-2xl border transition-all" :class="config.reefType === 'Fresh' ? 'bg-blue-600/20 border-blue-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.reefType === 'Fresh' ? 'text-blue-400' : 'text-slate-500'">Fresh Water</span>
                        <span class="text-white text-xs font-bold italic">Classic River View</span>
                    </button>
                </div>

                <!-- Системы жизнеобеспечения -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Smart Filtration Control</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Eheim / Fluval Pro</p>
                        </div>
                        <button @click="config.automation = !config.automation" class="w-12 h-6 rounded-full relative transition-all" :class="config.automation ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.automation ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">CO2 Injection System</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Plant Growth Booster</p>
                        </div>
                        <button @click="config.co2System = !config.co2System" class="w-12 h-6 rounded-full relative transition-all" :class="config.co2System ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.co2System ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Marine Panel -->
            <div class="bg-blue-600/10 p-8 rounded-3xl border border-blue-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-blue-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Толщина стекла (мм)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.glassThickness + ' мм.'"></span>
                    </div>
                    <div class="border-l border-blue-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Вес (кг.)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.totalWeight + ' кг.'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплекс Ocean Premium:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveAquarium()" class="w-full py-6 bg-blue-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-blue-500 active:scale-95 transition-all shadow-2xl shadow-blue-500/20">
                СФОРМИРОВАТЬ ЭКОСИСТЕМУ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Профессиональный расчет гидродинамики и биобаланса.</p>
        </div>
    </div>
</div>
