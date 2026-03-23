@php
    declare(strict_types=1);
    // Интеграция с доменным сервисом систем безопасности
    $calculatorService = app(\App\Domains\ConstructionMaterials\Services\MaterialCalculatorService::class);
@endphp

<div x-data="{
    config: { securityLevel: 'Standard', cameraCount: 4, sensorCount: 12, biometrics: false, cloudDays: 30, dispatchCenter: true },
    correlationId: '{{ Str::uuid() }}',

    get shieldPower() {
        let base = this.config.securityLevel === 'Military' ? 95 : (this.config.securityLevel === 'Advanced' ? 82 : 65);
        return base + (this.config.biometrics ? 5 : 0);
    },

    get totalPrice() {
        let levelCost = this.config.securityLevel === 'Military' ? 250000 : (this.config.securityLevel === 'Advanced' ? 95000 : 35000);
        let hardware = (this.config.cameraCount * 12500) + (this.config.sensorCount * 3200);
        let options = (this.config.biometrics ? 45000 : 0) + (this.config.dispatchCenter ? 15000 : 0);
        return Math.round(levelCost + hardware + options);
    },

    formatPrice(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[800px] font-sans italic tracking-tighter uppercase">
        
        <!-- Security Viz HUD -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 group/viz shadow-2xl italic uppercase tracking-tighter leading-none">
            <div class="absolute top-10 left-10 z-40">
                <div class="flex items-center space-x-3 bg-black/40 backdrop-blur-3xl px-5 py-2.5 rounded-full border border-red-500/30">
                    <div class="w-2.5 h-2.5 bg-red-600 rounded-full animate-ping shadow-[0_0_10px_#dc2626]"></div>
                    <span class="text-[10px] text-red-100 font-black uppercase tracking-widest italic tracking-tighter" x-text="'Active Shield: ' + config.securityLevel"></span>
                </div>
            </div>

            <!-- Scanning Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden bg-[radial-gradient(circle_at_50%_50%,#450a0a_0%,#020617_100%)] italic tracking-tighter leading-none uppercase">
                <div class="relative w-full max-w-md aspect-square group-hover/viz:scale-[1.05] transition-transform duration-700 italic tracking-tighter leading-none uppercase">
                    <svg viewBox="0 0 400 400" class="w-full opacity-60 italic tracking-tighter leading-none uppercase">
                        <!-- Perimeter -->
                        <rect x="50" y="50" width="300" height="300" rx="20" fill="none" stroke="#ef4444" stroke-width="1" stroke-dasharray="10 5" opacity="0.4" />
                        
                        <!-- Scanning Beam -->
                        <g class="origin-center animate-[spin_4s_linear_infinite] italic tracking-tighter leading-none uppercase">
                            <path d="M 200 200 L 400 150 L 400 250 Z" fill="url(#scanGradient)" opacity="0.3" />
                        </svg>
                        
                        <!-- Camera Nodes -->
                        <template x-for="i in 8">
                            <circle :cx="200 + 140 * Math.cos(i * Math.PI / 4)" 
                                    :cy="200 + 140 * Math.sin(i * Math.PI / 4)" 
                                    r="3" fill="#ef4444" class="animate-pulse italic tracking-tighter leading-none uppercase" />
                        </template>

                        <defs>
                            <linearGradient id="scanGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#ef4444" stop-opacity="0" />
                                <stop offset="100%" stop-color="#ef4444" stop-opacity="0.8" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center italic tracking-tighter leading-none uppercase">
                        <span class="text-6xl font-black text-white italic tracking-tighter block shadow-xl italic tracking-tighter leading-none uppercase" x-text="shieldPower + '%'"></span>
                        <span class="text-[12px] text-red-400 font-black tracking-[0.3em] mt-4 italic tracking-tighter leading-none uppercase">SHIELD STRENGTH</span>
                    </div>
                </div>
            </div>

            <div class="p-10 grid grid-cols-2 gap-6 relative z-30 italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl italic tracking-tighter leading-none uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 italic tracking-tighter leading-none">Detection Nodes</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase" x-text="config.sensorCount"></span>
                </div>
                <div class="bg-black/60 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/10 text-center shadow-xl border-t-4 border-t-red-500 italic tracking-tighter leading-none uppercase">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-3 italic tracking-tighter leading-none">Security Budget</span>
                    <span class="text-3xl text-red-500 font-black italic tracking-tighter leading-none italic tracking-tighter leading-none uppercase" x-text="formatPrice(totalPrice)"></span>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[3rem] border border-white/5 shadow-inner leading-none uppercase italic tracking-tighter leading-none">
            <div class="mb-14">
                <div class="inline-block px-5 py-2 rounded-full bg-red-700 text-white mb-6 shadow-2xl leading-none italic tracking-tighter uppercase font-sans tracking-tighter">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] font-sans tracking-tighter italic tracking-tighter leading-none uppercase">Threat Response Vector</span>
                </div>
                <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none tracking-tighter italic tracking-tighter leading-none uppercase">Titan Guard</h3>
            </div>

            <div class="flex-grow space-y-10 px-2 italic tracking-tighter leading-none">
                <div class="space-y-6">
                    <div class="flex justify-between items-end mb-2 pr-2 leading-none italic tracking-tighter leading-none uppercase">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block italic leading-none tracking-tighter italic tracking-tighter leading-none uppercase">IP Optic Sensors (Count)</span>
                        <span class="text-3xl font-black text-white italic tracking-tighter uppercase italic tracking-tighter leading-none uppercase leading-none font-sans" x-text="config.cameraCount"></span>
                    </div>
                    <input type="range" x-model="config.cameraCount" min="2" max="32" step="2" class="w-full h-1.5 bg-white/10 rounded-full appearance-none cursor-pointer accent-red-500 transition-all italic tracking-tighter leading-none uppercase">
                </div>

                <div class="grid grid-cols-3 gap-3 italic tracking-tighter leading-none uppercase">
                    <template x-for="lvl in ['Standard', 'Advanced', 'Military']">
                        <button @click="config.securityLevel = lvl" 
                                class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest leading-none shadow-xl italic tracking-tighter leading-none uppercase":class="config.securityLevel === lvl ? 'bg-red-700 text-white border-red-500 shadow-[0_0_15px_#dc262644]' : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                            <span x-text="lvl"></span>
                        </button>
                    </template>
                </div>

                <div class="space-y-4 pt-4 border-t border-white/10 italic tracking-tighter leading-none uppercase">
                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-red-500 transition-all italic tracking-tighter leading-none uppercase">
                        <div class="text-left italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter leading-none uppercase italic tracking-tighter font-sans">Biometric Auth Core</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic tracking-tighter leading-none italic tracking-tighter leading-none">Liveness / Retina Scan</span>
                        </div>
                        <button @click="config.biometrics = !config.biometrics" class="w-14 h-7 rounded-full relative transition-all shadow-inner italic tracking-tighter leading-none uppercase" :class="config.biometrics ? 'bg-red-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md italic tracking-tighter leading-none uppercase" :style="config.biometrics ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group hover:border-red-500 transition-all italic tracking-tighter leading-none uppercase">
                        <div class="text-left italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                            <span class="text-lg font-black block text-white italic leading-none tracking-tighter uppercase italic tracking-tighter font-sans">Remote Dispatch Link</span>
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block mt-2 leading-none italic tracking-tighter leading-none">24/7 Monitoring Response</span>
                        </div>
                        <button @click="config.dispatchCenter = !config.dispatchCenter" class="w-14 h-7 rounded-full relative transition-all shadow-inner italic tracking-tighter leading-none uppercase" :class="config.dispatchCenter ? 'bg-red-600' : 'bg-white/10'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all shadow-md italic tracking-tighter leading-none uppercase" :style="config.dispatchCenter ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total Price Panel -->
            <div class="mt-14 p-10 bg-white/5 rounded-[3.5rem] border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden transition-all italic tracking-tighter leading-none uppercase italic tracking-tighter leading-none uppercase">
                <div class="absolute inset-x-0 h-[1px] bg-red-500 top-0 opacity-40 italic tracking-tighter leading-none uppercase"></div>
                <div class="flex items-center justify-between mb-8 relative z-20 italic tracking-tighter leading-none uppercase">
                    <div class="italic tracking-tighter leading-none uppercase">
                        <span class="text-[12px] text-red-500 uppercase font-black block tracking-[0.2em] mb-4 italic italic tracking-tighter leading-none uppercase">Guard-Core Budget Allocation</span>
                        <span class="text-6xl font-black text-white italic tracking-tighter leading-none shadow-xl italic tracking-tighter leading-none uppercase" x-text="formatPrice(totalPrice)"></span>
                    </div>
                </div>
                <button class="w-full bg-red-700 hover:bg-red-600 text-slate-950 font-black py-7 rounded-2xl transition-all shadow-2xl uppercase italic text-sm tracking-[0.2em] flex items-center justify-center space-x-5 active:scale-95 group italic tracking-tighter leading-none uppercase">
                    <span>Activate Defense Grid</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform group-hover:rotate-[20deg] transition-transform italic tracking-tighter leading-none uppercase">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
