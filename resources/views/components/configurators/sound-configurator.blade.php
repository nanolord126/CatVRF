@props(['template'])

<div x-data="soundConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Акустики (Wave Propagation SVG) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(rgba(139,92,246,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(139,92,246,0.05)_1px,transparent_1px)] bg-[size:20px_20px]"></div>
            
            <svg viewBox="0 0 600 600" class="w-full max-h-[400px] drop-shadow-[0_20px_40px_rgba(139,92,246,0.2)] transition-all duration-700">
                <!-- Room Perimeter -->
                <rect x="100" y="100" width="400" height="400" fill="none" stroke="rgba(139,92,246,0.2)" stroke-width="2" />
                
                <!-- Sound Absorbers (Panels) -->
                <template x-for="panel in config.panels" :key="panel.id">
                    <rect :x="panel.x" :y="panel.y" :width="panel.w" :height="panel.h" :fill="panel.type === 'diffuser' ? '#8b92f6' : '#c084fc'" rx="2" class="opacity-60" />
                </template>

                <!-- Sound Waves Animation -->
                <template x-if="config.testing">
                    <g transform="translate(300, 300)">
                        <template x-for="i in 5">
                            <circle r="0" fill="none" stroke="#8b5cf6" stroke-width="2">
                                <animate attributeName="r" from="0" to="250" :dur="1.5 + i * 0.2 + 's'" repeatCount="indefinite" />
                                <animate attributeName="opacity" from="1" to="0" :dur="1.5 + i * 0.2 + 's'" repeatCount="indefinite" />
                            </circle>
                        </template>
                    </g>
                </template>

                <!-- Frequency Analyzer Wall -->
                <g transform="translate(100, 100)">
                    <template x-for="(bar, i) in 20">
                        <rect :x="i * 20" :y="400 - (Math.sin(i * 0.5) * 50 + 100)" width="18" :height="Math.sin(i * 0.5) * 50 + 100" fill="rgba(139,92,246,0.1)" />
                    </template>
                </g>

                <!-- Annotations -->
                <text x="60" y="40" fill="#a78bfa" font-size="10" font-weight="bold" font-family="monospace">ABSORPTION: <span x-text="config.reduction"></span> dB</text>
                <text x="60" y="55" fill="#a78bfa" font-size="10" font-weight="bold" font-family="monospace">REVERB (T60): <span x-text="config.reverb"></span>s</text>
            </svg>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4 uppercase font-black italic text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="config.status === 'optimal' ? 'bg-purple-500 animate-pulse' : 'bg-amber-500'"></div>
                    <span class="text-white" x-text="'Acoustic Status: ' + config.status"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-purple-600 pb-4 leading-none">
                Sonic <span class="text-purple-500 text-6xl block mt-2">Shield AI</span>
            </h1>

            <div class="space-y-6">
                <!-- Тип Решения -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Целевая Акустика</span>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="config.mode = 'recording'" class="p-3 rounded-xl border transition-all text-left" :class="config.mode === 'recording' ? 'bg-purple-600/20 border-purple-500 shadow-[0_0_15px_rgba(139,92,246,0.2)]' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-xs leading-none">Studio Dead</span>
                            <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block">High Absorption</span>
                        </button>
                        <button @click="config.mode = 'listening'" class="p-3 rounded-xl border transition-all text-left" :class="config.mode === 'listening' ? 'bg-purple-600/20 border-purple-500 shadow-[0_0_15px_rgba(139,92,246,0.2)]' : 'bg-white/5 border-white/10'">
                            <span class="text-white font-black italic text-xs leading-none">Live Space</span>
                            <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block">Balanced Reflection</span>
                        </button>
                    </div>
                </div>

                <!-- Выбор Панелей -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Акустик-Панели (шт)</span>
                        <input type="number" x-model="config.panelCount" min="4" max="40" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-purple-400">
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-2">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Бас-ловушки (шт)</span>
                        <input type="number" x-model="config.bassTraps" min="2" max="12" class="bg-transparent border-none text-white text-3xl font-black italic w-full focus:outline-none focus:text-purple-400 text-right">
                    </div>
                </div>

                <!-- Материал Заполнения -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Наполнитель перегородок</span>
                    <select x-model="config.core" class="bg-transparent border-none text-white text-lg font-black italic w-full focus:outline-none focus:text-purple-400 appearance-none uppercase bg-slate-900">
                        <option value="stone">Rockwool 80kg/m³</option>
                        <option value="poly">Polyester Echo-Fiber</option>
                        <option value="lead">Mass Loaded Vinyl (MLV)</option>
                    </select>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-purple-600/10 p-8 rounded-3xl border border-purple-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-purple-500/10 uppercase font-black italic tracking-widest">
                    <div>
                        <span class="text-slate-500 text-[9px] block mb-1">STC Rating (ГКЛ+)</span>
                        <span class="text-2xl text-white leading-none">58 <span class="text-xs">dB</span></span>
                    </div>
                    <div class="border-l border-purple-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] block mb-1">Коэф. Absorption</span>
                        <span class="text-2xl text-white leading-none">0.85 <span class="text-xs">NRC</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Studio Pro 2026 Edition:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveAcoustics()" class="w-full py-6 bg-purple-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-purple-500 active:scale-95 transition-all shadow-2xl shadow-purple-500/20 uppercase leading-none">
                ЗАПУСТИТЬ ТЕСТ АКУСТИКИ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold">Расчет по стандартам ISO 3382. Включает монтажную схему и звукоизоляционные подрозетники.</p>
        </div>
    </div>
</div>
