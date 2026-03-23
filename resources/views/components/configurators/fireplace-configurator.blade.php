@props(['template'])

<div x-data="fireplaceConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-orange-500/10 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12 font-black italic uppercase leading-none italic font-black uppercase leading-none font-black italic uppercase leading-none uppercase leading-none font-black leading-none">
        <!-- Визуализация (Fire Animation SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-orange-900/20 via-transparent to-transparent opacity-50 font-black italic uppercase leading-none"></div>
            
            <svg viewBox="0 0 700 500" class="w-full max-h-[400px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Outer Casing -->
                <rect x="150" y="100" width="400" height="300" fill="none" stroke="#fff" stroke-width="1" rx="10" opacity="0.1" />
                
                <!-- Internal Burner Line -->
                <line x1="200" y1="350" x2="500" y2="350" stroke="#f97316" stroke-width="4" stroke-linecap="round" opacity="0.4" />

                <!-- Fire Simulation (Vapor/Flame) -->
                <g x-show="config.active">
                    <template x-for="i in 30">
                        <path :d="'M' + (200 + Math.random()*300) + ' 350 Q' + (200 + Math.random()*300) + ' 250 ' + (200 + Math.random()*300) + ' 150'" fill="none" :stroke="Math.random() > 0.5 ? '#f97316' : '#ea580c'" :stroke-width="2 + Math.random()*4" opacity="0.6">
                            <animate attributeName="d" :values="'M' + (200 + Math.random()*300) + ' 350; M' + (200 + Math.random()*300) + ' 200; M' + (200 + Math.random()*300) + ' 350'" dur="1.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0.6;0;0.6" dur="1.5s" repeatCount="indefinite" />
                        </path>
                    </template>
                </g>

                <!-- Glass Shield -->
                <rect x="170" y="120" width="360" height="260" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" rx="5" />
                
                <!-- Reflections -->
                <path d="M180 130 L300 130 L180 250 Z" fill="#fff" opacity="0.03" />
            </svg>

            <!-- Thermal Sensor -->
            <div class="absolute bottom-10 right-10 p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col font-black italic uppercase text-[9px] tracking-widest leading-none">
                <span class="text-slate-500 uppercase leading-none font-black italic">SENS-TEMP</span>
                <span class="text-orange-500 text-xl font-black italic leading-none whitespace-nowrap mt-1 leading-none uppercase leading-none">680°C</span>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8 italic font-black uppercase leading-none leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none">
            <h1 class="text-4xl font-black text-white italic tracking-tighter border-b border-white/10 pb-4 leading-none uppercase leading-none font-black italic leading-none uppercase font-black italic leading-none uppercase font-black italic font-black uppercase tracking-tighter leading-none italic leading-none uppercase leading-none">
                Ignis <span class="text-orange-500 text-6xl block mt-2 leading-none uppercase font-black italic leading-none uppercase leading-none leading-none font-black italic leading-none">Bio-Fireplace</span>
            </h1>

            <div class="space-y-6 italic leading-none font-black uppercase tracking-widest leading-none font-black whitespace-nowrap leading-none uppercase leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase">
                <!-- Dimensions -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-5 leading-none uppercase leading-none leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase leading-none h-4 uppercase leading-none">Длина горелки (мм)</span>
                    <input type="number" x-model="config.length" step="100" min="500" max="3000" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-orange-500 italic font-black leading-none">
                </div>

                <!-- Fuel Technology -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4 font-black italic uppercase leading-none whitespace-nowrap underline leading-none leading-none uppercase font-black tracking-widest leading-none uppercase leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none uppercase leading-none font-black italic leading-none underline leading-none uppercase tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none">Тип топлива</span>
                    <div class="grid grid-cols-2 gap-2 font-black italic uppercase font-black italic leading-none uppercase leading-none font-black leading-none uppercase">
                        <template x-for="tech in ['Bio-Ethanol DX', 'Electric-Vapor', 'Natural Gas', 'Hybrid Fuel']">
                            <button @click="config.fuel = tech" class="p-2 rounded-lg border text-[9px] font-black italic transition-all leading-none italic leading-none uppercase font-black leading-none uppercase underline leading-none font-black italic leading-none underline leading-none" :class="config.fuel === tech ? 'bg-orange-900 border-orange-600 text-white' : 'bg-white/5 border-white/10 text-slate-500'" x-text="tech"></button>
                        </template>
                    </div>
                </div>

                <!-- Stealth Shield -->
                <div class="space-y-4 uppercase leading-none font-black italic leading-none font-black tracking-widest leading-none uppercase leading-none underline leading-none uppercase font-black italic tracking-widest leading-none leading-none uppercase leading-none">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10 italic leading-none leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none">
                        <div>
                            <span class="text-white text-sm font-black italic block uppercase leading-none font-black italic leading-none">Invisible Safety Shield</span>
                            <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1 leading-none italic leading-none uppercase underline leading-none">Ceramic Heat-Stop</p>
                        </div>
                        <button @click="config.shield = !config.shield" class="w-10 h-5 rounded-full relative transition-all uppercase leading-none leading-none" :class="config.shield ? 'bg-orange-700 shadow-[0_0_8px_rgba(249,115,22,0.4)]' : 'bg-slate-700 font-black italic leading-none'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all leading-none uppercase font-black italic leading-none" :style="config.shield ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Engineering Metrics -->
            <div class="bg-orange-900/10 p-8 rounded-3xl border border-orange-900/40 space-y-4 font-black italic uppercase leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-orange-900/20 italic font-black leading-none uppercase leading-none font-black italic leading-none">
                    <div>
                        <span class="text-orange-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none">Heat Output</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none"><span x-text="(config.length / 100).toFixed(1)"></span> <span class="text-xs uppercase leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase">kW/H</span></span>
                    </div>
                    <div class="border-l border-orange-900/20 pl-4 text-right italic font-black leading-none uppercase leading-none">
                        <span class="text-orange-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase">Burn Time</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none">8-12 <span class="text-[10px] italic font-black uppercase leading-none">HOURS</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none">
                    <span class="text-orange-400 font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none italic underline leading-none uppercase font-black italic tracking-widest leading-none">Custom Creation Price:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter uppercase font-black leading-none italic leading-none transition-all duration-300 font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none whitespace-nowrap leading-none"><span x-text="formatPrice(totalPrice)"></span></span>
                </div>
            </div>

            <button @click="config.active = !config.active" class="w-full py-6 bg-orange-900 hover:bg-orange-800 text-white rounded-3xl font-black italic tracking-widest transition-all shadow-2xl shadow-orange-900/30 uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none uppercase font-black leading-none uppercase underline leading-none">
                <span x-text="config.active ? 'ВЫКЛЮЧИТЬ ПЛАМЯ' : 'ЗАЖЕЧЬ ПЛАМЯ'"></span>
            </button>
            <p class="text-[9px] text-slate-500 italic uppercase tracking-tighter text-center mt-4 italic leading-none font-black italic leading-none uppercase leading-none italic leading-none uppercase underline leading-none uppercase leading-none font-black leading-none italic font-black italic leading-none uppercase underline leading-none">Zero-chimney required technology. Smart-Home Tasmota/Zigbee integration ready.</p>
        </div>
    </div>
</div>
