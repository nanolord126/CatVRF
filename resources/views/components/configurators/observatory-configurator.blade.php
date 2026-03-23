@props(['template'])

<div x-data="observatoryConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-indigo-500/10 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12 font-black italic uppercase font-black italic leading-none font-black italic uppercase leading-none font-black leading-none">
        <!-- Визуализация (Observatory Dome SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden font-black italic uppercase leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-indigo-900/20 via-transparent to-transparent opacity-50 font-black italic uppercase leading-none"></div>
            
            <svg viewBox="0 0 700 500" class="w-full max-h-[400px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Dome Structure -->
                <path d="M150 400 A200 200 0 0 1 550 400" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2" />
                <path d="M150 400 L550 400" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                
                <!-- Shutter Mechanism (Open/Close) -->
                <g :transform="config.open ? 'translate(0, -20)' : 'translate(0, 0)'" class="transition-all duration-1000">
                    <rect x="330" y="100" width="40" height="150" fill="rgba(99,102,241,0.2)" stroke="#6366f1" stroke-width="1" rx="2" />
                </g>

                <!-- Telescope Silhouette -->
                <g transform="translate(350, 250) rotate(-30)" :opacity="config.open ? 1 : 0.2">
                    <rect x="-10" y="-100" width="20" height="150" fill="#fff" rx="2" />
                    <circle cx="0" cy="50" r="15" fill="none" stroke="#6366f1" stroke-width="2" />
                </g>

                <!-- Star Tracking Lines -->
                <g x-show="config.tracking">
                    <template x-for="i in 10">
                        <circle :cx="Math.random()*700" :cy="Math.random()*300" :r="0.5" fill="#fff">
                            <animate attributeName="opacity" values="0.2;1;0.2" :dur="1 + Math.random()*2 +'s'" repeatCount="indefinite" />
                        </circle>
                    </template>
                </g>
            </svg>

            <!-- Coordinate Widget -->
            <div class="absolute bottom-10 left-10 p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col font-black italic uppercase text-[9px] tracking-widest leading-none">
                <span class="text-slate-500 uppercase leading-none font-black italic">RA/DEC TRACK</span>
                <span class="text-indigo-500 text-lg font-black italic leading-none whitespace-nowrap mt-1 leading-none uppercase leading-none" x-text="config.tracking ? '14h 29m / -62° 40\'' : 'OFFLINE'"></span>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8 italic font-black uppercase leading-none leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black leading-none italic font-black leading-none italic font-black leading-none">
            <h1 class="text-4xl font-black text-white italic tracking-tighter border-b border-white/10 pb-4 leading-none uppercase leading-none font-black italic leading-none uppercase font-black italic leading-none uppercase font-black italic font-black uppercase tracking-tighter leading-none italic leading-none uppercase leading-none">
                Stellar <span class="text-indigo-500 text-6xl block mt-2 leading-none uppercase font-black italic leading-none uppercase leading-none leading-none font-black italic leading-none">Observatory DX</span>
            </h1>

            <div class="space-y-6 italic leading-none font-black uppercase tracking-widest leading-none font-black whitespace-nowrap leading-none uppercase leading-none uppercase leading-none font-black whitespace-nowrap leading-none uppercase">
                <!-- Aperture Diameter -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-5 leading-none uppercase leading-none leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase leading-none h-4 uppercase leading-none">Апертура зеркала (мм)</span>
                    <input type="number" x-model="config.aperture" step="50" min="200" max="2500" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-indigo-500 italic font-black leading-none">
                </div>

                <!-- Mount Technology -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4 font-black italic uppercase leading-none whitespace-nowrap underline leading-none leading-none uppercase font-black tracking-widest leading-none uppercase leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none uppercase leading-none font-black italic leading-none underline leading-none uppercase tracking-widest leading-none uppercase font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none">Тип монтировки</span>
                    <div class="grid grid-cols-2 gap-2 font-black italic uppercase font-black italic leading-none uppercase leading-none font-black leading-none uppercase">
                        <template x-for="tech in ['Equatorial Pro', 'Alt-Az Robotic', 'Direct-Drive', 'Precision-X']">
                            <button @click="config.mount = tech" class="p-2 rounded-lg border text-[9px] font-black italic transition-all leading-none italic leading-none uppercase font-black leading-none uppercase underline leading-none font-black italic leading-none underline leading-none" :class="config.mount === tech ? 'bg-indigo-900 border-indigo-600 text-white' : 'bg-white/5 border-white/10 text-slate-500'" x-text="tech"></button>
                        </template>
                    </div>
                </div>

                <!-- Imaging Sensors -->
                <div class="space-y-4 uppercase leading-none font-black italic leading-none font-black tracking-widest leading-none uppercase leading-none underline leading-none uppercase font-black italic tracking-widest leading-none leading-none uppercase leading-none">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10 italic leading-none leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none">
                        <div>
                            <span class="text-white text-sm font-black italic block uppercase leading-none font-black italic leading-none">Deep-Sky Imaging Kit</span>
                            <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1 leading-none italic leading-none uppercase underline leading-none">Cooled CMOS 61MP</p>
                        </div>
                        <button @click="config.imaging = !config.imaging" class="w-10 h-5 rounded-full relative transition-all uppercase leading-none leading-none" :class="config.imaging ? 'bg-indigo-700 shadow-[0_0_8px_rgba(99,102,241,0.4)]' : 'bg-slate-700 font-black italic leading-none'">
                            <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all leading-none uppercase font-black italic leading-none" :style="config.imaging ? 'left: 23px' : 'left: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Optical Metrics -->
            <div class="bg-indigo-900/10 p-8 rounded-3xl border border-indigo-900/40 space-y-4 font-black italic uppercase leading-none leading-none uppercase leading-none font-black italic leading-none font-black leading-none uppercase leading-none font-black leading-none italic">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-indigo-900/20 italic font-black leading-none uppercase leading-none font-black italic leading-none">
                    <div>
                        <span class="text-indigo-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none">Light Gathering</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none">x<span x-text="(config.aperture / 7).toFixed(0)"></span> <span class="text-xs uppercase leading-none font-black italic leading-none underline leading-none uppercase font-black italic leading-none underline leading-none uppercase">EYE</span></span>
                    </div>
                    <div class="border-l border-indigo-900/20 pl-4 text-right italic font-black leading-none uppercase leading-none">
                        <span class="text-indigo-500 text-[9px] block mb-1 uppercase tracking-widest leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase">Limiting Mag</span>
                        <span class="text-2xl text-white font-black italic leading-none uppercase font-black leading-none underscore leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black italic leading-none underline leading-none font-black leading-none uppercase whitespace-nowrap leading-none">+16.5 <span class="text-[10px] italic font-black uppercase leading-none">MAG</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none">
                    <span class="text-indigo-400 font-black italic text-[10px] uppercase leading-none leading-none font-black italic leading-none font-black leading-none italic underline leading-none uppercase font-black italic tracking-widest leading-none">Observatory Project Price:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter uppercase font-black leading-none italic leading-none transition-all duration-300 font-black italic leading-none underline leading-none uppercase leading-none font-black leading-none whitespace-nowrap leading-none"><span x-text="formatPrice(totalPrice)"></span></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button @click="config.open = !config.open" class="py-6 border border-indigo-500 text-white rounded-3xl font-black italic tracking-widest transition-all uppercase leading-none font-black italic leading-none uppercase leading-none underline leading-none">
                    <span x-text="config.open ? 'ЗАКРЫТЬ КУПОЛ' : 'ОТКРЫТЬ КУПОЛ'"></span>
                </button>
                <button @click="config.tracking = !config.tracking" class="py-6 bg-indigo-900 hover:bg-indigo-800 text-white rounded-3xl font-black italic tracking-widest transition-all shadow-2xl shadow-indigo-900/30 uppercase leading-none font-black italic leading-none uppercase leading-none underline leading-none">
                    <span x-text="config.tracking ? 'СТОП ТРЕКИНГ' : 'ПУСК ТРЕКИНГ'"></span>
                </button>
            </div>
            <p class="text-[9px] text-slate-500 italic uppercase tracking-tighter text-center mt-4 italic leading-none font-black italic leading-none uppercase leading-none italic leading-none uppercase underline leading-none uppercase leading-none font-black leading-none italic font-black italic leading-none uppercase underline leading-none">Full computer-controlled automation. Weather sensor & safety shutdown included.</p>
        </div>
    </div>
</div>
