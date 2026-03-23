@props(['template'])

<div x-data="outdoorKitchenConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация (Modular 3D Block Mockup) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-orange-500/5 via-transparent to-transparent opacity-50"></div>
            
            <svg viewBox="0 0 800 500" class="w-full max-h-[400px] drop-shadow-[0_40px_60px_rgba(0,0,0,0.8)] transition-all duration-700">
                <!-- Isometric Counter Base -->
                <g transform="translate(100, 300)">
                    <!-- Left Module (Sink) -->
                    <path d="M0 0 L150 -75 L300 0 L150 75 Z" fill="#334155" stroke="#475569" stroke-width="2" />
                    <path d="M0 0 L0 100 L150 175 L150 75 Z" fill="#1e293b" stroke="#475569" stroke-width="2" />
                    <path d="M150 75 L150 175 L300 100 L300 0 Z" fill="#0f172a" stroke="#475569" stroke-width="2" />
                    
                    <!-- Middle Module (Grill) -->
                    <g transform="translate(150, -75)">
                        <path d="M0 0 L150 -75 L300 0 L150 75 Z" fill="#475569" stroke="#94a3b8" stroke-width="2" />
                        <path d="M0 0 L0 100 L150 175 L150 75 Z" fill="#334155" />
                        <!-- Grill Hood -->
                        <path d="M50 -25 L250 -25 L250 25 L50 25 Z" fill="#000" rx="5" opacity="0.8">
                            <animate attributeName="opacity" values="0.8;1;0.8" dur="3s" repeatCount="indefinite" />
                        </path>
                        <!-- Heat Distortion -->
                        <template x-if="config.heat">
                            <g transform="translate(150, -10)">
                                <path d="M-50 0 Q-25 -40 0 0 Q25 -40 50 0" fill="none" stroke="orange" stroke-width="1" opacity="0.4">
                                    <animate attributeName="d" values="M-50 0 Q-25 -40 0 0 Q25 -40 50 0; M-50 0 Q-25 -60 0 0 Q25 -60 50 0" dur="2s" repeatCount="indefinite" />
                                </path>
                            </g>
                        </template>
                    </g>
                    
                    <!-- Right Module (Worktop) -->
                    <g transform="translate(300, -150)">
                        <path d="M0 0 L150 -75 L300 0 L150 75 Z" fill="#334155" stroke="#475569" stroke-width="2" />
                        <path d="M150 75 L150 175 L300 100 L300 0 Z" fill="#0f172a" />
                    </g>
                </g>

                <!-- Annotations -->
                <text x="50" y="50" fill="#fb923c" font-size="10" font-family="monospace font-black italic uppercase leading-none">BTU: <span x-text="config.totalBtu"></span>k</text>
                <text x="650" y="50" fill="#94a3b8" font-size="10" font-family="monospace font-black italic uppercase leading-none">MATERIAL: <span x-text="config.material"></span></text>
            </svg>

            <!-- Status Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4 uppercase font-black italic text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2 font-black italic uppercase leading-none">
                    <div class="w-1.5 h-1.5 rounded-full" :class="config.gas ? 'bg-orange-500 animate-pulse' : 'bg-slate-700'"></div>
                    <span class="text-white uppercase leading-none" x-text="'FUEL: ' + config.fuelType"></span>
                </div>
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2 font-black italic uppercase leading-none">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                    <span class="text-white uppercase leading-none" x-text="'DURABILITY: GRADE-A'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8 italic uppercase leading-none uppercase leading-none font-black tracking-widest leading-none">
            <h1 class="text-4xl font-black text-white italic tracking-tighter border-b border-white/10 pb-4 uppercase leading-none leading-none">
                Terra <span class="text-orange-500 text-6xl block mt-2 leading-none uppercase leading-none">Outdoor Kitchen</span>
            </h1>

            <div class="space-y-6 italic font-black uppercase leading-none leading-none">
                <!-- Modules Configuration -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4 font-black italic uppercase leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none leading-none">Активные модули</span>
                    <div class="grid grid-cols-2 gap-2 uppercase leading-none">
                        <button @click="toggleModule('grill')" class="p-3 rounded-xl border text-[10px] font-black italic transition-all leading-none" :class="config.modules.grill ? 'bg-orange-600/20 border-orange-500 text-white' : 'bg-white/5 border-white/10 text-slate-500'">PROF-GRILL XL</button>
                        <button @click="toggleModule('sink')" class="p-3 rounded-xl border text-[10px] font-black italic transition-all leading-none" :class="config.modules.sink ? 'bg-orange-600/20 border-orange-500 text-white' : 'bg-white/5 border-white/10 text-slate-500'">SINK-UNIT 304</button>
                        <button @click="toggleModule('fridge')" class="p-3 rounded-xl border text-[10px] font-black italic transition-all leading-none" :class="config.modules.fridge ? 'bg-orange-600/20 border-orange-500 text-white' : 'bg-white/5 border-white/10 text-slate-500'">ICE-COOLER DX</button>
                        <button @click="toggleModule('pizza')" class="p-3 rounded-xl border text-[10px] font-black italic transition-all leading-none" :class="config.modules.pizza ? 'bg-orange-600/20 border-orange-500 text-white' : 'bg-white/5 border-white/10 text-slate-500'">STONE-PIZZA OVEN</button>
                    </div>
                </div>

                <!-- Material Selection -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-5 font-black italic uppercase leading-none">
                    <span class="text-slate-500 text-[9px] block tracking-widest leading-none leading-none">Материал фасадов</span>
                    <div class="flex space-x-2">
                        <template x-for="mat in ['High-Gloss Steel', 'Brushed Slate', 'Iroko Wood', 'Volcanic Stone']">
                            <button @click="config.material = mat" class="px-3 py-2 rounded-lg border text-[8px] font-black italic transition-all leading-none italic uppercase leading-none" :class="config.material === mat ? 'bg-orange-600 border-orange-500 text-white shadow-[0_0_10px_rgba(251,146,60,0.3)]' : 'bg-white/5 border-white/10 text-slate-500'" x-text="mat"></button>
                        </template>
                    </div>
                </div>

                <!-- Countertop Choice -->
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10 font-black italic uppercase leading-none leading-none uppercase leading-none leading-none">
                    <div>
                        <span class="text-white text-sm font-black italic block leading-none leading-none uppercase whitespace-nowrap">Integrated BBQ Light</span>
                        <p class="text-[8px] text-slate-500 uppercase font-black tracking-widest mt-1 leading-none italic leading-none whitespace-nowrap">Night Vision 360°</p>
                    </div>
                    <button @click="config.lighting = !config.lighting" class="w-10 h-5 rounded-full relative transition-all uppercase leading-none" :class="config.lighting ? 'bg-orange-600 shadow-[0_0_8px_rgba(251,146,60,0.4)]' : 'bg-slate-700'">
                        <div class="absolute top-1 w-3 h-3 bg-white rounded-full transition-all uppercase leading-none" :style="config.lighting ? 'left: 23px' : 'left: 4px'"></div>
                    </button>
                </div>
            </div>

            <!-- Price & Engineering -->
            <div class="bg-orange-600/10 p-8 rounded-3xl border border-orange-500/20 space-y-4 font-black italic uppercase leading-none leading-none">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-orange-500/10 font-black italic uppercase leading-none leading-none">
                    <div>
                        <span class="text-orange-500 text-[9px] block mb-1 uppercase italic leading-none whitespace-nowrap italic leading-none uppercase tracking-widest leading-none">Total Burners</span>
                        <span class="text-2xl text-white leading-none leading-none italic font-black uppercase tracking-tighter uppercase whitespace-nowrap"><span x-text="config.burners"></span> <span class="text-xs italic uppercase italic font-black leading-none">Elements</span></span>
                    </div>
                    <div class="border-l border-orange-500/10 pl-4 text-right leading-none uppercase font-black leading-none">
                        <span class="text-orange-500 text-[9px] block mb-1 uppercase tracking-widest leading-none leading-none">Weather Class</span>
                        <span class="text-2xl text-white leading-none leading-none uppercase italic font-black uppercase text-xs uppercase italic leading-none uppercase italic leading-none italic">Marine <span class="text-xs uppercase italic leading-none">316L</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end uppercase italic font-black leading-none leading-none">
                    <span class="text-orange-400 italic font-black uppercase text-[10px] leading-none whitespace-nowrap">Professional Grade:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter uppercase whitespace-nowrap tracking-tighter uppercase leading-none italic font-black italic leading-none"><span x-text="formatPrice(totalPrice)"></span></span>
                </div>
            </div>

            <button @click="confirmProject()" class="w-full py-6 bg-orange-600 hover:bg-orange-500 text-white rounded-3xl font-black italic tracking-widest transition-all shadow-2xl shadow-orange-500/30 uppercase leading-none font-black italic tracking-widest leading-none leading-none">
                СФОРМИРОВАТЬ СМЕТУ
            </button>
            <p class="text-[9px] text-slate-500 italic uppercase tracking-tighter text-center mt-4 leading-none font-black italic tracking-widest italic tracking-tighter uppercase leading-none leading-none leading-none">All components are UV-resistant. High-grade industrial granite & steel only. Lifetime frame warranty.</p>
        </div>
    </div>
</div>
