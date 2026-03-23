@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом фундаментов и бетона
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { inlineSize: 10, blockSize: 10, depth: 80, slabThickness: 30 },
    foundationType: 'Slab',
    concreteGrade: 'M300',
    armatureEnabled: true,
    correlationId: '{{ Str::uuid() }}',

    get surfaceArea() { return this.config.inlineSize * this.config.blockSize; },
    
    get volume() {
        if (this.foundationType === 'Slab') {
            return this.surfaceArea * (this.config.slabThickness / 100);
        }
        // Simplified Tape Foundation calculation
        let perimeter = (this.config.inlineSize + this.config.blockSize) * 2;
        return perimeter * 0.4 * (this.config.depth / 100);
    },

    get serviceData() {
        return {
            'M200': { price: 4500 },
            'M300': { price: 5200 },
            'M400': { price: 6100 }
        }[this.concreteGrade];
    },

    get totalPrice() {
        let concreteCost = this.volume * this.serviceData.price;
        let armatureCost = this.armatureEnabled ? (this.volume * 2500) : 0;
        return Math.round(concreteCost + armatureCost);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Foundation Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz font-sans italic tracking-tighter">
            <div class="absolute top-10 left-10 z-40 font-sans italic tracking-tighter leading-none">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-emerald-500/30 font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                    <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_10px_#10b981]"></div>
                    <span class="text-[10px] text-emerald-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Ground-Core: ' + foundationType"></span>
                </div>
            </div>

            <!-- Foundation Projection Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#064e3b_0%,#020617_100%)] font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                <div class="relative w-full max-w-md aspect-video group-hover/viz:scale-[1.05] transition-transform duration-700 font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">
                    <svg viewBox="0 0 500 300" class="w-full drop-shadow-2xl">
                        <path :d="`M 100 200 L 250 250 L 400 200 L 250 150 Z`" fill="#334155" stroke="#475569" stroke-width="2" />
                        <path :d="`M 100 200 L 100 ${200 + (config.depth/4)} L 250 ${250 + (config.depth/4)} L 250 250 Z`" fill="#1e293b" />
                        <path :d="`M 400 200 L 400 ${200 + (config.depth/4)} L 250 ${250 + (config.depth/4)} L 250 250 Z`" fill="#0f172a" />
                    </svg>
                </div>
            </div>

            <div class="p-10 grid grid-cols-3 gap-6 relative z-30 font-sans italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Concrete (M³)</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans" x-text="volume.toFixed(1)"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl font-sans italic tracking-tighter leading-none uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none uppercase font-sans italic tracking-tighter leading-none uppercase">Depth (CM)</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none font-sans italic tracking-tighter leading-none italic" x-text="config.depth"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-emerald-500">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 leading-none italic tracking-tighter">Reinforced</span>
                    <span class="text-2xl font-black italic tracking-tighter uppercase leading-none font-sans" :class="armatureEnabled ? 'text-emerald-500' : 'text-slate-500'" x-text="armatureEnabled ? 'ON' : 'OFF'"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none">
            <div class="mb-14 font-sans italic tracking-tighter leading-none italic">
                <div class="inline-block px-5 py-2 rounded-full bg-emerald-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none">Foundation Core v.2026</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter">Concrete Node</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 font-sans italic tracking-tighter leading-none">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none font-sans italic tracking-tighter">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter font-sans italic tracking-tighter leading-none italic tracking-tighter">Area Inline-Size (m)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter leading-none italic font-sans italic tracking-tighter leading-none italic" x-text="config.inlineSize"></span>
                    </div>
                    <input type="range" x-model="config.inlineSize" min="2" max="30" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-4 font-sans italic tracking-tighter leading-none italic">
                    <template x-for="type in ['Slab', 'Tape']">
                        <button @click="foundationType = type" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl font-sans"
                                :class="foundationType === type ? 'bg-emerald-700 text-white border-emerald-600' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="type"></span>
                        </button>
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-4 border-t border-white/10 font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-emerald-500 transition-all font-sans italic tracking-tighter leading-none italic">
                        <div class="text-left font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic [app-design-guide] Surface Area-Flow v.2026">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter">Armature Cage</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic">Standard reinforcement</span>
                        </div>
                        <button @click="armatureEnabled = !armatureEnabled" class="w-14 h-7 rounded-full relative transition-all shadow-inner font-sans italic tracking-tighter leading-none italic" :class="armatureEnabled ? 'bg-emerald-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md" :style="armatureEnabled ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Budget Output -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all font-sans italic tracking-tighter leading-none italic uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-emerald-500 top-0 opacity-40 font-sans italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none italic"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 font-sans italic tracking-tighter leading-none">
                    <div class="font-sans italic tracking-tighter leading-none uppercase">
                        <span class="text-[12px] text-emerald-500 uppercase font-black block tracking-[0.2em] mb-4 italic leading-none font-sans italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none italic tracking-tighter leading-none italic">Structure Injection Budget</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl font-sans" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group font-sans italic tracking-tighter leading-none italic">
                    <span>Finalize Concrete Order</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[30deg] transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                    <!-- Верхняя грань (Топ фундамента) -->
                    <path :d="`M 250 ${350 - (config.depth/10)} L 450 ${250 - (config.depth/10)} L 250 ${150 - (config.depth/10)} L 50 ${250 - (config.depth/10)} Z`" fill="#94a3b8" stroke="#cbd5e1" stroke-width="2" />
                    
                    <!-- Арматурная сетка (Pattern) -->
                    <template x-if="config.foundationType === 'slab'">
                        <path :d="`M 100 ${230 - (config.depth/10)} L 400 ${230 - (config.depth/10)} M 120 ${210 - (config.depth/10)} L 380 ${210 - (config.depth/10)}`" stroke="#f59e0b" stroke-width="1" stroke-dasharray="2 4" />
                    </template>
                </g>

                <!-- Аннотации -->
                <text x="50" y="220" fill="#22c55e" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">DEPTH: <span x-text="config.depth"></span>мм</text>
                <text x="350" y="380" fill="#22c55e" font-size="10" stroke="#000" stroke-width="0.1" font-weight="bold" font-family="monospace">LOAD: <span x-text="results.bearingCapacity"></span>кг/м²</text>
            </svg>

            <!-- Технический виджет -->
            <div class="absolute top-10 right-10 flex flex-col items-end space-y-2">
                <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-[9px] text-emerald-400 font-black italic uppercase tracking-widest">Soil Impact Analysis</span>
                <span class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl text-[10px] text-white font-bold italic" x-text="'CONCRETE: ' + config.concreteGrade"></span>
            </div>
        </div>

        <!-- Органы управления -->
        <div class="w-full lg:w-2/5 flex flex-col space-y-8">
            <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-b border-emerald-500 pb-4">
                Foundation <span class="text-emerald-500">Core 2.0</span>
            </h1>

            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Тип фундамента</span>
                        <select x-model="config.foundationType" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500 transition-all">
                            <option value="slab">Монолитная плита</option>
                            <option value="strip">Ленточный (MZLF)</option>
                            <option value="piles">Свайно-ростверковый</option>
                        </select>
                    </label>
                    <label>
                        <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 mb-2 block">Марка бетона</span>
                        <select x-model="config.concreteGrade" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl italic py-3 px-4 outline-none">
                            <option value="M250">M250 (B20)</option>
                            <option value="M300">M300 (B22.5)</option>
                            <option value="M350">M350 (B25)</option>
                        </select>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Глубина / Высота (мм)</span>
                        <input type="number" x-model.number="config.depth" min="100" max="2500" step="50" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-emerald-400">
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Арматура Ø (мм)</span>
                        <select x-model.number="config.armature" class="bg-transparent border-none text-white text-xl font-bold italic w-full focus:outline-none focus:text-emerald-400 appearance-none">
                            <option value="12">12 mm A500C</option>
                            <option value="14">14 mm A500C</option>
                            <option value="16">16 mm A500C</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic leading-none block">Гидроизоляция</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">Technonicol Prime Plus</p>
                        </div>
                        <button @click="config.waterproof = !config.waterproof" class="w-12 h-6 rounded-full relative transition-all" :class="config.waterproof ? 'bg-emerald-600' : 'bg-slate-700'">
                            <div class="absolute inset-block-start-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.waterproof ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div>
                            <span class="text-white text-sm font-bold italic leading-none block">Утепление торцов</span>
                            <p class="text-[9px] text-slate-500 uppercase font-black tracking-tighter mt-1">XPS Carbon ECO 50mm</p>
                        </div>
                        <button @click="config.insulation = !config.insulation" class="w-12 h-6 rounded-full relative transition-all" :class="config.insulation ? 'bg-emerald-600' : 'bg-slate-700'">
                            <div class="absolute inset-block-start-1 w-4 h-4 bg-white rounded-full transition-all" :style="config.insulation ? 'inset-inline-start: 27px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Materials Panel -->
            <div class="bg-emerald-600/10 p-8 rounded-3xl border border-emerald-500/20 space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-emerald-500/10">
                    <div>
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Бетон (M³)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.volume + ' м³'"></span>
                    </div>
                    <div class="border-l border-emerald-500/10 pl-4 text-right">
                        <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Арматура (TM)</span>
                        <span class="text-2xl font-black text-white italic" x-text="results.armatureWeight + ' тн'"></span>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-slate-400 italic font-bold uppercase text-[10px]">Полная смета работ + материалы:</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>

            <button @click="saveFoundation()" class="w-full py-6 bg-emerald-600 text-white rounded-3xl font-black italic tracking-widest hover:bg-emerald-500 active:scale-95 transition-all shadow-2xl shadow-emerald-500/20">
                СФОРМИРОВАТЬ СМЕТУ ДЛЯ БРИГАДЫ
            </button>
            <p class="text-[9px] text-slate-600 italic uppercase tracking-tighter text-center leading-relaxed">Расчет выполнен по СП 50-101-2004. Требуется геологическое исследование грунта.</p>
        </div>
    </div>
</div>
