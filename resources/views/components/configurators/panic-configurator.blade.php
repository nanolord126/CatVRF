@props(['template'])

<div x-data="panicConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Бункера (Isometric Section SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(239,68,68,0.05),transparent)] animate-pulse"></div>
            
            <svg viewBox="0 0 600 600" class="w-full max-h-[400px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Concrete Shell (Isometric) -->
                <path d="M 100 250 L 300 150 L 500 250 L 500 450 L 300 550 L 100 450 Z" fill="#1e293b" stroke="#334155" stroke-width="4" />
                <path d="M 100 250 L 300 350 L 500 250" fill="none" stroke="#334155" stroke-width="2" />
                <path d="M 300 350 L 300 550" fill="none" stroke="#334155" stroke-width="2" />

                <!-- Steel Door Unit -->
                <rect x="250" y="380" width="80" height="120" fill="#0f172a" stroke="#ef4444" stroke-width="2" rx="4" transform="skewY(15)" />
                <circle cx="280" cy="445" r="10" fill="none" stroke="#ef4444" stroke-width="2" /> <!-- Locking Wheel -->

                <!-- Internal Modules -->
                <template x-if="config.lifeSupport">
                    <g transform="translate(150, 300)">
                        <rect width="40" height="60" fill="#06b6d4" opacity="0.4" rx="2" />
                        <text x="20" y="75" text-anchor="middle" fill="#06b6d4" font-size="8" font-weight="bold" font-family="monospace uppercase">Hepo-Air</text>
                    </g>
                </template>

                <template x-if="config.comms">
                    <g transform="translate(410, 300)">
                        <rect width="40" height="60" fill="#f59e0b" opacity="0.4" rx="2" />
                        <text x="20" y="75" text-anchor="middle" fill="#f59e0b" font-size="8" font-weight="bold" font-family="monospace uppercase">Sat-Com</text>
                    </g>
                </template>

                <!-- Zone Scan Animation -->
                <rect x="100" y="150" width="400" height="400" fill="none">
                    <animate attributeName="opacity" values="0.1;0.3;0.1" dur="4s" repeatCount="indefinite" />
                </rect>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#ef4444" font-size="10" font-weight="bold" font-family="monospace">PROTECTION: <span x-text="config.shieldLevel"></span> BR</text>
                <text x="60" y="55" fill="#ef4444" font-size="10" font-weight="bold" font-family="monospace">AUTONOMY: <span x-text="config.autonomy"></span> DAYS</text>
            </svg>

            <!-- Status Widget -->
            <div class="absolute top-10 right-10 flex flex-col items-end space-y-2 uppercase font-black italic text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-red-600 border border-red-500 rounded-xl flex items-center space-x-2 animate-pulse">
                    <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                    <span class="text-white">SECURE MODE: ACTIVE</span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-red-600 pb-4">
                Aegis <span class="text-red-600 text-6xl block mt-2">Panic Hub</span>
            </h1>

            <div class="space-y-6">
                <!-- Класс защиты -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none text-center">Класс бронирования (CEN 1063)</span>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="config.shieldLevel = 'BR4'" class="p-3 rounded-xl border flex flex-col items-center transition-all" :class="config.shieldLevel === 'BR4' ? 'bg-red-600/20 border-red-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-sm leading-none">BR4</span>
                            <span class="text-[7px] text-slate-500 font-black mt-1 uppercase leading-none">Handgun</span>
                        </button>
                        <button @click="config.shieldLevel = 'BR6'" class="p-3 rounded-xl border flex flex-col items-center transition-all" :class="config.shieldLevel === 'BR6' ? 'bg-red-600/20 border-red-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-sm leading-none">BR6</span>
                            <span class="text-[7px] text-slate-500 font-black mt-1 uppercase leading-none">Assault</span>
                        </button>
                        <button @click="config.shieldLevel = 'BR7'" class="p-3 rounded-xl border flex flex-col items-center transition-all" :class="config.shieldLevel === 'BR7' ? 'bg-red-600/20 border-red-500' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-sm leading-none">BR7</span>
                            <span class="text-[7px] text-slate-500 font-black mt-1 uppercase leading-none">AP-Rifle</span>
                        </button>
                    </div>
                </div>

                <!-- Опции выживания -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center space-x-3 p-4 bg-white/5 rounded-2xl border border-white/10 cursor-pointer" @click="config.lifeSupport = !config.lifeSupport">
                        <div class="w-4 h-4 rounded-full border border-red-500 flex items-center justify-center transition-all" :class="config.lifeSupport ? 'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]' : ''"></div>
                        <span class="text-white text-[10px] font-black italic uppercase tracking-widest leading-none">Air Filtration</span>
                    </div>
                    <div class="flex items-center space-x-3 p-4 bg-white/5 rounded-2xl border border-white/10 cursor-pointer" @click="config.comms = !config.comms">
                        <div class="w-4 h-4 rounded-full border border-red-500 flex items-center justify-center transition-all" :class="config.comms ? 'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]' : ''"></div>
                        <span class="text-white text-[10px] font-black italic uppercase tracking-widest leading-none">Sat Link</span>
                    </div>
                </div>

                <!-- Площадь и Вместимость -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-2 leading-none">Площадь (м²)</span>
                        <input type="number" x-model="config.area" min="6" max="50" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-red-400">
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-2 leading-none">Персон</span>
                        <input type="number" x-model="config.capacity" min="1" max="10" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-red-400 text-right">
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-red-600/10 p-8 rounded-3xl border border-red-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-red-500/10 uppercase font-black italic tracking-widest">
                    <div>
                        <span class="text-slate-500 text-[9px] block mb-1">Толщина стали</span>
                        <span class="text-2xl text-white leading-none">25 <span class="text-xs">mm</span></span>
                    </div>
                    <div class="border-l border-red-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] block mb-1">Вес модуля</span>
                        <span class="text-2xl text-white leading-none">4.2 <span class="text-xs">tons</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Vault-Tec Extreme Core:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="savePanicSystem()" class="w-full py-6 bg-red-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-red-500 active:scale-95 transition-all shadow-2xl shadow-red-500/20 uppercase">
                ЗАКЛАДКА ФУНДАМЕНТА
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold leading-none">Установка производится сертифицированными инженерами. Полная конфиденциальность. Стандарт ГОСТ Р 50941.</p>
        </div>
    </div>
</div>
