@php
    declare(strict_types=1);
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
@endphp

<div x-data="{
    bottleCount: 120,
    targetTemp: 12,
    rackMaterial: 'Oak',
    humidityControl: true,
    uvProtection: true,
    engineState: 'idle',
    showTelemetry: true,
    
    get coolingPower() {
        return (this.bottleCount * 0.05 + (this.targetTemp < 10 ? 2 : 1)).toFixed(2);
    },
    
    get baseCost() {
        let base = this.bottleCount * 450;
        if (this.rackMaterial === 'Metal') base += 15000;
        if (this.humidityControl) base += 8500;
        if (this.uvProtection) base += 4200;
        return base;
    },

    formatValue(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(185, 28, 28, 0.2) 1px, transparent 0); background-size: 30px 30px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[850px] relative z-10">
        
        <!-- Visual Section: Wine Vault Simulation -->
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden flex flex-col border border-white/5 shadow-inner">
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-20">
                <div class="absolute inset-inline-0 h-[2px] bg-red-600/50 blur-sm animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-8 left-8 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-3 bg-black/60 backdrop-blur-xl px-4 py-2 rounded-lg border-l-2 border-red-600 shadow-lg">
                    <div class="w-2 h-2 bg-red-600 rounded-full animate-pulse shadow-[0_0_8px_rgba(220,38,38,0.8)]"></div>
                    <span class="text-[10px] text-red-500 font-black uppercase tracking-[0.2em] italic">Vault-Core / v.2026.B</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-3 py-1 rounded text-[8px] text-slate-500 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                    <span>ID: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Climate Telemetry HUD -->
            <div class="absolute top-8 right-8 z-20 flex flex-col space-y-4 text-right">
                <div x-show="showTelemetry" class="space-y-3">
                    <div class="p-3 bg-black/60 backdrop-blur-md rounded-xl border border-white/5 group/stat transition-all hover:bg-red-950/20">
                        <span class="text-[8px] text-red-500 font-black uppercase tracking-widest block mb-1">Target Thermal</span>
                        <span class="text-xl text-white font-black italic" x-text="targetTemp + '°C'"></span>
                    </div>
                    <div class="p-3 bg-black/60 backdrop-blur-md rounded-xl border border-white/5 group/stat transition-all hover:bg-red-950/20">
                        <span class="text-[8px] text-red-500 font-black uppercase tracking-widest block mb-1">Humidity RH</span>
                        <span class="text-xl text-white font-black italic" x-text="humidityControl ? '65%' : 'N/A'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization -->
            <div class="flex-grow flex items-center justify-center p-16 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(185,28,28,0.1)_0%,transparent_60%)]"></div>
                
                <!-- Optical Grid Crosshair -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-10">
                    <div class="w-[80%] h-[1px] bg-red-600/30"></div>
                    <div class="h-[80%] w-[1px] bg-red-600/30"></div>
                </div>

                <svg viewBox="0 0 500 500" class="w-full h-full drop-shadow-[0_0_50px_rgba(185,28,28,0.2)] filter contrast-125" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <filter id="wineGlow">
                            <feGaussianBlur stdDeviation="2" result="blur"/>
                            <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                        </filter>
                    </defs>

                    <!-- Vault Structure -->
                    <rect x="50" y="50" width="400" height="400" fill="none" stroke="rgba(185,28,28,0.4)" stroke-width="3" rx="10" />
                    
                    <!-- Racks -->
                    <template x-for="i in 8">
                        <line x1="50" :y1="50 + i*50" x2="450" :y2="50 + i*50" stroke="rgba(185,28,28,0.2)" stroke-width="1.5" />
                    </template>
                    
                    <!-- Vertical Dividers -->
                    <template x-for="i in 10">
                        <line :x1="50 + i*40" y1="50" :x2="50 + i*40" y2="450" stroke="rgba(185,28,28,0.1)" stroke-width="1" />
                    </template>

                    <!-- Dynamic Bottle Matrix -->
                    <template x-for="row in 8">
                        <g>
                            <template x-for="col in 10">
                                <circle :cx="70 + (col-1)*40" :cy="75 + (row-1)*50" 
                                        :r="((row-1)*10 + col) <= (bottleCount/2) ? 6 : 0" 
                                        :fill="rackMaterial === 'Oak' ? '#450a0a' : '#1e293b'" 
                                        class="transition-all duration-700" 
                                        :style="`opacity: ${((row-1)*10 + col) % 3 === 0 ? '0.8' : '0.4'}`" />
                            </template>
                        </g>
                    </template>

                    <!-- Climate Sensor Marker -->
                    <g x-show="humidityControl" transform="translate(420, 70)">
                        <circle r="4" fill="#dc2626" class="animate-ping" />
                        <circle r="2" fill="#ef4444" />
                    </g>
                </svg>
            </div>

            <!-- Vault Specs HUD Footer -->
            <div class="p-8 grid grid-cols-3 gap-1 relative z-10 bg-black/40 backdrop-blur-md border-t border-white/10">
                <div class="bg-black/40 p-5 rounded-2xl border-l-2 border-red-600/50 group/stat">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Max Capacity</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="bottleCount + ' UNIT'"></span>
                </div>
                <div class="bg-black/40 p-5 rounded-2xl border-l-2 border-red-600/50 group/stat">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Cooling Load</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter" x-text="coolingPower + ' kW'"></span>
                </div>
                <div class="bg-black/40 p-5 rounded-2xl border-l-2 border-red-600/50 group/stat">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1">Rack Surface</span>
                    <span class="text-2xl text-white font-black italic tracking-tighter uppercase" x-text="rackMaterial"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Luxury Configuration -->
        <div class="bg-slate-900/40 p-10 lg:p-14 flex flex-col rounded-[2.5rem] border border-white/5 relative overflow-hidden backdrop-blur-xl">
            
            <div class="mb-14 relative text-right">
                <h3 class="text-4xl font-black text-white italic tracking-tighter uppercase leading-none border-r-4 border-red-600 pr-6 inline-block">Grand Vault</h3>
                <p class="text-[10px] text-red-500 font-extrabold uppercase tracking-[0.3em] mt-3 opacity-80 italic">Precision Enology Matrix</p>
            </div>

            <div class="flex-grow space-y-10">
                <!-- Rack Architecture -->
                <div class="p-6 bg-black/30 rounded-[2rem] border border-white/5 relative group/opt transition-all hover:bg-black/50">
                    <div class="flex justify-between items-center mb-6 pl-1">
                        <span class="text-[10px] text-red-500 font-black uppercase tracking-[0.2em]">01. Rack Array Matrix</span>
                        <div class="flex space-x-1">
                            <div class="w-1 h-1 bg-red-600 rounded-full"></div>
                            <div class="w-1 h-1 bg-red-600/40 rounded-full"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="rackMaterial = 'Oak'" 
                                :class="rackMaterial === 'Oak' ? 'bg-red-700 text-white shadow-[0_0_25px_rgba(185,28,28,0.4)] border-red-500' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10'"
                                class="py-5 rounded-2xl font-black italic uppercase text-[10px] transition-all tracking-widest border">Solid Heritage Oak</button>
                        <button @click="rackMaterial = 'Metal'" 
                                :class="rackMaterial === 'Metal' ? 'bg-red-700 text-white shadow-[0_0_25px_rgba(185,28,28,0.4)] border-red-500' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10'"
                                class="py-5 rounded-2xl font-black italic uppercase text-[10px] transition-all tracking-widest border">Polished Chrome Steel</button>
                    </div>
                </div>

                <!-- Parameters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none pl-1">Unit Count</span>
                            <span class="text-2xl font-black text-red-500 italic leading-none" x-text="bottleCount"></span>
                        </div>
                        <div class="relative py-2">
                            <input type="range" x-model="bottleCount" min="20" max="600" step="20" 
                               class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-red-600 hover:accent-red-500 transition-all">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2 border-l border-white/10 pl-6">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest block leading-none">Target Thermal</span>
                            <span class="text-2xl font-black text-red-500 italic leading-none" x-text="targetTemp + '°'"></span>
                        </div>
                        <div class="relative py-2 border-l border-white/10 pl-6">
                            <input type="range" x-model="targetTemp" min="6" max="22" step="1" 
                               class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-red-600 hover:accent-red-500 transition-all">
                        </div>
                    </div>
                </div>

                <!-- Climate Guard -->
                <div class="space-y-5">
                    <div class="flex items-center justify-between p-6 bg-black/40 rounded-3xl border border-white/5 group/toggle transition-all hover:border-red-600/30">
                        <div class="flex space-x-5 items-center">
                            <div class="w-12 h-12 bg-red-600/10 rounded-2xl flex items-center justify-center border border-red-600/20">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-base font-black italic block leading-none uppercase tracking-tighter">Humidity Guard Matrix</span>
                                <p class="text-[8px] text-slate-500 uppercase font-black tracking-[0.1em] mt-2">Active RH calibration 65-70%</p>
                            </div>
                        </div>
                        <button @click="humidityControl = !humidityControl" class="w-14 h-7 rounded-full relative transition-all duration-500 shadow-inner" :class="humidityControl ? 'bg-red-600 shadow-[0_0_15px_rgba(220,38,38,0.4)]' : 'bg-slate-800'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-500 shadow-xl" :style="humidityControl ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-black/40 rounded-3xl border border-white/5 group/toggle transition-all hover:border-red-600/30">
                        <div class="flex space-x-5 items-center">
                            <div class="w-12 h-12 bg-red-600/10 rounded-2xl flex items-center justify-center border border-red-600/20">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <div>
                                <span class="text-white text-base font-black italic block leading-none uppercase tracking-tighter">UV-Shield Crystal Matrix</span>
                                <p class="text-[8px] text-slate-500 uppercase font-black tracking-[0.1em] mt-2">Light filtration system 99.9% protection</p>
                            </div>
                        </div>
                        <button @click="uvProtection = !uvProtection" class="w-14 h-7 rounded-full relative transition-all duration-500 shadow-inner" :class="uvProtection ? 'bg-red-600 shadow-[0_0_15px_rgba(220,38,38,0.4)]' : 'bg-slate-800'">
                            <div class="absolute top-1 w-5 h-5 bg-white rounded-full transition-all duration-500 shadow-xl" :style="uvProtection ? 'inset-inline-start: 32px' : 'inset-inline-start: 4px'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total HUD -->
            <div class="mt-14 p-10 bg-red-700 rounded-[3rem] shadow-[0_30px_60px_rgba(185,28,28,0.3)] relative overflow-hidden group/total">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.1)_50%,transparent_75%)] bg-[length:200%_200%] animate-[shimmer_4s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <span class="text-[10px] font-black text-red-950 uppercase tracking-[0.3em] block mb-2">Total Project Valuation</span>
                        <div class="text-5xl font-black text-slate-950 italic tracking-tighter" x-text="formatValue(baseCost)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-10 py-5 rounded-[2rem] font-black uppercase text-[10px] tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-2xl">
                        Authorize Vault
                    </button>
                </div>
            </div>
            
            <p class="mt-6 text-[8px] text-slate-600 italic uppercase tracking-[0.3em] text-center">Engineered for rare vintage preservation. Automated climate lockdown supported.</p>
        </div>
    </div>
</div>
