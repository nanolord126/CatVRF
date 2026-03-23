@props(['template'])

<div x-data="restorationConfigurator(@js($template))" class="relative group p-6 bg-slate-900/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl overflow-hidden transition-all duration-300">
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Визуализация Реставрации (Split-View Image processing) -->
        <div class="w-full lg:w-3/5 bg-black/60 rounded-3xl min-h-[550px] border border-white/5 p-16 relative flex items-center justify-center overflow-hidden flex-col">
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-amber-950/10 blur-3xl rounded-full transition-all duration-700"></div>
            
            <!-- Photo Upload Area / Simulation -->
            <div class="relative w-full h-[400px] bg-slate-800/20 rounded-2xl border-2 border-dashed border-white/10 flex items-center justify-center group/upload overflow-hidden">
                <template x-if="!config.photo">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-white text-[10px] font-black italic tracking-widest leading-none uppercase">ЗАГРУЗИТЕ ФОТО ДЛЯ AI-АНАЛИЗА</span>
                    </div>
                </template>
                
                <template x-if="config.photo">
                    <div class="relative w-full h-full">
                        <!-- Simulated "Before" (Grey/Old) -->
                        <div class="absolute inset-0 bg-slate-700 grayscale transform translate-x-[-50%] group-hover/upload:grayscale-0 transition-all duration-1000"></div>
                        <!-- Simulated "After" (Color/New) -->
                        <div class="absolute inset-0 bg-amber-900 shadow-[inset_0_0_100px_rgba(0,0,0,0.5)] transform translate-x-[50%] transition-all duration-1000 border-l border-white/20"></div>
                        
                        <!-- AI Scan Line -->
                        <div class="absolute top-0 bottom-0 w-1 bg-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.5)] z-10" :style="'left: ' + config.scanLine + '%'"></div>
                    </div>
                </template>
            </div>

            <!-- Bio-Widget -->
            <div class="absolute bottom-10 left-10 flex space-x-4 uppercase font-black italic font-family:monospace text-[9px] tracking-widest leading-none">
                <div class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="config.photo ? 'bg-amber-500 animate-pulse' : 'bg-slate-700'"></div>
                    <span class="text-white" x-text="config.photo ? 'AI CONDITION: DAMAGED (82%)' : 'WAITING FOR SOURCE PHOTO'"></span>
                </div>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-amber-600 pb-4">
                Antique <span class="text-amber-500 text-6xl block mt-2">Revive AI</span>
            </h1>

            <div class="space-y-6">
                <!-- Состояние (Slider AI simulation) -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Глубина повреждений (%)</span>
                        <span class="text-white font-bold italic leading-none" x-text="config.damage + '%'"></span>
                    </div>
                    <input type="range" x-model="config.damage" min="10" max="100" class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500">
                </div>

                <!-- Выбор Процедур -->
                <div class="grid grid-cols-2 gap-3">
                    <button @click="config.polish = !config.polish" class="p-4 rounded-2xl border transition-all text-left" :class="config.polish ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-white font-black italic text-xs leading-none">Polishing</span>
                        <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block leading-none">Shellac Finish</span>
                    </button>
                    <button @click="config.veneer = !config.veneer" class="p-4 rounded-2xl border transition-all text-left" :class="config.veneer ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-white font-black italic text-xs leading-none">Veneering</span>
                        <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block leading-none">Inlay Repair</span>
                    </button>
                    <button @click="config.upholstery = !config.upholstery" class="p-4 rounded-2xl border transition-all text-left" :class="config.upholstery ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-white font-black italic text-xs leading-none">Upholstery</span>
                        <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block leading-none">Premium Fabric</span>
                    </button>
                    <button @click="config.carving = !config.carving" class="p-4 rounded-2xl border transition-all text-left" :class="config.carving ? 'bg-amber-600/20 border-amber-500' : 'bg-white/5 border-white/10'">
                        <span class="text-white font-black italic text-xs leading-none">Carving</span>
                        <span class="text-[7px] text-slate-500 font-black mt-1 uppercase block leading-none">Wood Restoration</span>
                    </button>
                </div>

                <!-- Тип дерева (Finish selection) -->
                <div class="p-5 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest leading-none">Финишное покрытие</span>
                    <select x-model="config.finish" class="bg-transparent border-none text-white text-lg font-black italic w-full focus:outline-none focus:text-amber-400 appearance-none bg-slate-900 uppercase">
                        <option value="oak">VINTAGE OAK</option>
                        <option value="cherry">ROYAL CHERRY</option>
                        <option value="mahogany">MAHOGANY DARK</option>
                        <option value="ebony">EBONY BLACK</option>
                    </select>
                </div>
            </div>

            <!-- Price Panel -->
            <div class="bg-amber-600/10 p-8 rounded-3xl border border-amber-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-amber-500/10 uppercase font-black italic tracking-widest">
                    <div>
                        <span class="text-slate-500 text-[9px] block mb-1">Сложность AI</span>
                        <span class="text-2xl text-white leading-none">LEVEL <span class="text-xs">4/5</span></span>
                    </div>
                    <div class="border-l border-amber-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] block mb-1">Время работы</span>
                        <span class="text-2xl text-white leading-none">12-24 <span class="text-xs">дня</span></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Restoration Core Elite:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="startAIRestore()" class="w-full py-6 bg-amber-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-amber-500 active:scale-95 transition-all shadow-2xl shadow-amber-500/20 uppercase leading-none">
                ОЦЕНИТЬ ШЕДЕВР AI
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed font-bold leading-none">Система обучена на 10.000+ антикварных объектах 18-20 веков. Применяются только аутентичные масла и лаки.</p>
        </div>
    </div>
</div>
