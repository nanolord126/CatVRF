@props(['template'])

<div x-data="hifiConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-rose-500/10 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12 font-black italic uppercase leading-none italic font-black uppercase leading-none font-black italic uppercase leading-none uppercase leading-none font-black leading-none">
        <!-- Визуализация (Acoustic Pressure SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-rose-900/10 via-transparent to-transparent opacity-50 font-black italic uppercase leading-none"></div>
            
            <svg viewBox="0 0 700 500" class="w-full max-h-[400px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Acoustic Panels -->
                <rect x="100" y="50" width="80" height="400" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" rx="4" />
                <rect x="520" y="50" width="80" height="400" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" rx="4" />
                
                <!-- Center Rack -->
                <rect x="250" y="300" width="200" height="150" fill="none" stroke="rgba(255,255,255,0.2)" rx="10" />

                <!-- Sound Wave Visualization -->
                <g x-show="config.active">
                    <template x-for="i in 5">
                        <circle cx="350" cy="150" :r="20 * i" fill="none" stroke="#f43f5e" :stroke-width="1.5 - (i*0.2)" opacity="0">
                            <animate attributeName="r" :values="20*i + ';' + (200 + 50*i)" dur="2s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0.8;0" dur="2s" repeatCount="indefinite" />
                        </circle>
                    </template>
                </g>

                <!-- Audio Components (Amps/DACS) -->
                <g transform="translate(260, 320)" fill="#fff" opacity="0.3">
                    <rect x="0" y="0" width="180" height="15" rx="2" />
                    <rect x="0" y="25" width="180" height="15" rx="2" />
                    <rect x="0" y="50" width="180" height="60" rx="5" /> <!-- Tube Amp -->
                </g>

                <!-- Speaker Silhouettes -->
                <path d="M120 100 L160 100 L170 400 L110 400 Z" fill="rgba(244,63,94,0.1)" stroke="#f43f5e" stroke-width="0.5" x-show="config.speakerType === 'floorstander'" />
            </svg>

            <!-- SPL Meter -->
            <div class="absolute top-10 left-10 p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col font-black italic uppercase text-[9px] tracking-widest leading-none">
                <span class="text-slate-500 uppercase leading-none font-black italic">SPL-DB</span>
                <span class="text-rose-500 text-xl font-black italic leading-none whitespace-nowrap mt-1 leading-none uppercase leading-none" x-text="config.active ? '114.2' : '0.0'"></span>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8 italic font-black uppercase leading-none leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none">
            <h1 class="text-4xl font-black text-white italic tracking-tighter border-b border-white/10 pb-4 leading-none uppercase leading-none font-black italic leading-none uppercase font-black italic leading-none uppercase font-black italic font-black uppercase tracking-tighter leading-none italic leading-none uppercase leading-none">
                Sonic <span class="text-rose-500 text-6xl block mt-2 leading-none uppercase font-black italic leading-none uppercase leading-none leading-none font-black italic leading-none">Audiophile DX</span>
            </h1>

            <div class="space-y-6 italic leading-none font-black uppercase tracking-widest leading-none font-black whitespace-nowrap leading-none uppercase leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase">
                <!-- Power Factor -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-5 leading-none uppercase leading-none leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase leading-none h-4 uppercase leading-none">Запас мощности (Watts)</span>
                    <input type="number" x-model="config.power" step="50" min="100" max="5000" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-rose-500 italic font-black leading-none">
                </div>

                <!-- Driver Tech -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4 font-black italic uppercase leading-none whitespace-nowrap underline leading-none leading-none uppercase font-black tracking-widest leading-none uppercase leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none uppercase leading-none font-black italic leading-none underline leading-none uppercase tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none">Технология драйверов</span>
                    <div class="grid grid-cols-2 gap-2 font-black italic uppercase font-black italic leading-none uppercase leading-none font-black leading-none uppercase">
                        <template x-for="tech in ['Beryllium DX', 'Nano-Carbon', 'Electrostatic', 'Ribbon-Hybrid']">
                            <button @click="config.tech = tech" class="p-2 rounded-lg border text-[9px] font-black italic transition-all leading-none italic leading-none uppercase font-black leading-none uppercase underline leading-none font-black italic leading-none underline leading-none" :class="config.tech === tech ? 'bg-rose-900 border-rose-600 text-white' : 'bg-white/5 border-white/10 text-slate-500'" x-text="tech"></button>
                        </template>
                    </div>
                </div>

                <!-- Acoustic Audit -->
                <div class="space-y-4 uppercase leading-none font-black italic leading-none font-black tracking-widest leading-none uppercase leading-none underline leading-none uppercase font-black italic tracking-widest leading-none leading-none uppercase leading-none">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10 italic leading-none leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none">
                        <div>
                            <span class="text-white text-sm font-black italic block uppercase leading-none font-black italic leading-none">Precision Calibration</span>
                            <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1 leading-none italic leading-none uppercase underline leading-none">Room Echo Compensation</p>
                        </div>
                        <button @click="config.audit = !config.audit" class="w-10 h-5 rounded-full relative transition-all uppercase leading-none leading-none" :class="config.audit ? 'bg-rose-700 shadow-[0_0_8px_rgba(244,63,94,0.4)]' : 'bg-slate-700 font-black italic leading-none'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all leading-none uppercase font-black italic leading-none" :style="config.audit ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sonic Metrics -->
            <div class="bg-rose-900/10 p-8 rounded-3xl border border-rose-900/40 space-y-4 font-black italic uppercase leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-rose-900/20 italic font-black leading-none uppercase leading-none font-black italic leading-none">
                    <div>
                        <span class="text-rose-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none">THD Distortion</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none"><0.0001% <span class="text-xs uppercase leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase">DX</span></span>
                    </div>
                    <div class="border-l border-rose-900/20 pl-4 text-right italic font-black leading-none uppercase leading-none">
                        <span class="text-rose-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase">Jitter Level</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none"><span x-text="config.audit ? '1.2' : '4.8'"></span> <span class="text-[10px] italic font-black uppercase leading-none">PS</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none">
                    <span class="text-rose-400 font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none italic underline leading-none uppercase font-black italic tracking-widest leading-none">Reference Audio Price:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter uppercase font-black leading-none italic leading-none transition-all duration-300 font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none whitespace-nowrap leading-none"><span x-text="formatPrice(totalPrice)"></span></span>
                </div>
            </div>

            <button @click="config.active = !config.active" class="w-full py-6 bg-rose-900 hover:bg-rose-800 text-white rounded-3xl font-black italic tracking-widest transition-all shadow-2xl shadow-rose-900/30 uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none uppercase font-black leading-none uppercase underline leading-none">
                <span x-text="config.active ? 'СТОП РЕЗОНАНС' : 'ОЖИВИТЬ ЗВУК'"></span>
            </button>
            <p class="text-[9px] text-slate-500 italic uppercase tracking-tighter text-center mt-4 italic leading-none font-black italic leading-none uppercase leading-none italic leading-none uppercase underline leading-none uppercase leading-none font-black leading-none italic font-black italic leading-none uppercase underline leading-none">Balanced XLR Interconnects included. Roon Ready & MQA Decoded.</p>
        </div>
    </div>
</div>
