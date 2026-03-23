@props(['template'])

<div x-data="evConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Зарядки (Power flow SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-blue-950/20 blur-3xl rounded-full"></div>
            
            <svg viewBox="0 0 500 500" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(59,130,246,0.1)] transition-all duration-700">
                <!-- Wallbox Unit -->
                <rect x="200" y="100" width="100" height="150" fill="#1e293b" rx="10" />
                <rect x="210" y="110" width="80" height="60" fill="#3b82f6" opacity="0.3" rx="5" />
                
                <!-- LED Status Ring -->
                <circle cx="250" cy="200" r="15" fill="none" :stroke="config.charging ? '#3b82f6' : '#10b981'" stroke-width="4" :class="config.charging ? 'animate-pulse' : ''" />

                <!-- Cable Path -->
                <path d="M 250 250 Q 250 400 400 400" stroke="#1e293b" stroke-width="12" fill="none" stroke-linecap="round" />
                <path d="M 400 380 L 440 380 L 440 420 L 400 420 Z" fill="#1e293b" /> <!-- Connector -->

                <!-- Flow Animation -->
                <template x-if="config.charging">
                    <g>
                        <template x-for="i in 5">
                            <circle r="3" fill="#60a5fa">
                                <animateMotion :path="'M 250 250 Q 250 400 400 400'" dur="1s" :begin="i * 0.2 + 's'" repeatCount="indefinite" />
                            </circle>
                        </template>
                    </g>
                </template>
                
                <!-- Annotations -->
                <text x="60" y="40" fill="#3b82f6" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">POWER: <span x-text="config.power"></span> kW</text>
                <text x="320" y="480" fill="#3b82f6" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">SMART: OCPP 1.6J</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <span class="text-white text-[10px] font-bold italic uppercase tracking-wider" x-text="'Load Balancer: ' + config.balancer"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-blue-600 pb-4">
                EV <span class="text-blue-500 text-6xl block mt-2">Charge AI</span>
            </h1>

            <div class="space-y-6">
                <!-- Параметры -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Мощность зарядки</span>
                        <select x-model="config.power" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-blue-400 appearance-none">
                            <option value="7.4" class="bg-slate-900 border-none">7.4 kW (1P)</option>
                            <option value="11" class="bg-slate-900 border-none">11 kW (3P)</option>
                            <option value="22" class="bg-slate-900 border-none">22 kW (3P)</option>
                        </select>
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Длина кабеля</span>
                        <input type="number" x-model.number="config.cableLength" min="3" max="15" step="1" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-blue-400">
                    </div>
                </div>

                <!-- Тип коннектора -->
                <div class="grid grid-cols-2 gap-3 text-left">
                    <button @click="config.connector = 'Type2'" class="p-4 rounded-2xl border transition-all" :class="config.connector === 'Type2' ? 'bg-blue-600/20 border-blue-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.connector === 'Type2' ? 'text-blue-400' : 'text-slate-500'">IEC Type 2</span>
                        <span class="text-white text-xs font-bold italic">Mennekes Std</span>
                    </button>
                    <button @click="config.connector = 'GBT'" class="p-4 rounded-2xl border transition-all" :class="config.connector === 'GBT' ? 'bg-blue-600/20 border-blue-500' : 'bg-white/5 border-white/10'">
                        <span class="text-[9px] uppercase font-black block tracking-widest mb-1" :class="config.connector === 'GBT' ? 'text-blue-400' : 'text-slate-500'">GB/T (CN)</span>
                        <span class="text-white text-xs font-bold italic">Direct Import</span>
                    </button>
                </div>

                <!-- Смарт Опции -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">PV-Solar Integration</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Charge from Sun Only</p>
                        </div>
                        <button @click="config.solar = !config.solar" class="w-12 h-6 rounded-full relative transition-all" :class="config.solar ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.solar ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic block leading-none">Dynamic Load Balancing</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Protect Home Fuse</p>
                        </div>
                        <button @click="config.dynamicLoad = !config.dynamicLoad" class="w-12 h-6 rounded-full relative transition-all" :class="config.dynamicLoad ? 'bg-blue-600' : 'bg-slate-700'">
                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.dynamicLoad ? 'left: 27px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-blue-600/10 p-8 rounded-3xl border border-blue-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-blue-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Время до полн. (100kWh)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.time + ' ч'"></span>
                    </div>
                    <div class="border-l border-blue-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Степень защиты</span>
                        <span class="text-2xl font-black text-white italic">IP65 / IK10</span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Комплектация Charge Core Pro:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveCharge()" class="w-full py-6 bg-blue-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-blue-500 active:scale-95 transition-all shadow-2xl shadow-blue-500/20">
                СФОРМИРОВАТЬ СТАНЦИЮ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет на основе оборудования ABB / Schneider Electric / Wallbox. Соответствует SAE J1772.</p>
        </div>
    </div>
</div>
