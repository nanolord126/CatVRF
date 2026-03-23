@php
    declare(strict_types=1);
    $calculator = app(\App\Services\MaterialCalculatorService::class);
    $correlationId = (string) str()->uuid();
@endphp

<div x-data="{
    area: 40,
    flooring: 'Recycled Rubber 20mm',
    cardioEnabled: true,
    powerRack: true,
    aiMirror: false,
    soundSystem: true,
    showTelemetry: true,
    
    get totalCost() {
        let base = this.area * 12500;
        if (this.cardioEnabled) base += 250000;
        if (this.powerRack) base += 180000;
        if (this.aiMirror) base += 120000;
        if (this.soundSystem) base += 65000;
        return base;
    },

    formatValue(val) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val);
    }
}" class="w-full bg-slate-950 rounded-[3.5rem] p-1 border border-white/5 shadow-2xl overflow-hidden group relative">
    
    <!-- HUD Background Grid -->
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(circle at 2px 2px, rgba(99, 102, 241, 0.2) 1px, transparent 0); background-size: 30px 30px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 h-full min-h-[900px] relative z-10 font-sans">
        
        <!-- Visual Section: Iron-Hub Simulation -->
        <div class="relative bg-slate-900 rounded-[3rem] overflow-hidden flex flex-col border border-white/5 shadow-inner transition-all duration-700">
            
            <!-- HUD Scanner Overlay -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-10">
                <div class="absolute inset-inline-0 h-[3px] bg-indigo-500/50 blur-md animate-scanline"></div>
            </div>

            <!-- Header HUD -->
            <div class="absolute top-10 left-10 z-20 flex flex-col space-y-2">
                <div class="flex items-center space-x-4 bg-black/60 backdrop-blur-2xl px-5 py-2.5 rounded-xl border-l-4 border-indigo-500 shadow-[0_0_30px_rgba(79,70,229,0.3)]">
                    <div class="w-2.5 h-2.5 bg-indigo-500 rounded-full animate-pulse shadow-[0_0_12px_rgba(79,70,229,0.8)]"></div>
                    <span class="text-[10px] text-indigo-400 font-black uppercase tracking-[0.3em] font-black italic">Strength-Core / v.26.H</span>
                </div>
                <div class="bg-black/40 backdrop-blur-sm px-4 py-1.5 rounded-lg text-[8px] text-slate-500 font-bold uppercase tracking-widest border border-white/5 inline-flex items-center space-x-2">
                    <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                    <span>Correlation: {{ substr($correlationId, 0, 8) }}</span>
                </div>
            </div>

            <!-- Kinetic Telemetry HUD -->
            <div class="absolute top-10 right-10 z-20 flex flex-col space-y-4 text-right">
                <div x-show="showTelemetry" class="space-y-3">
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-indigo-950/30">
                        <span class="text-[8px] text-indigo-400 font-black uppercase tracking-widest block mb-1 font-black italic leading-none">Load Capacity</span>
                        <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none" x-text="'12.5 kN/m²'"></span>
                    </div>
                    <div class="p-4 bg-black/70 backdrop-blur-xl rounded-2xl border border-white/5 group/stat transition-all hover:bg-indigo-950/30">
                        <span class="text-[8px] text-indigo-400 font-black uppercase tracking-widest block mb-1 font-black italic leading-none">S-Acoustic Buffer</span>
                        <span class="text-2xl text-white font-black italic tracking-tighter uppercase leading-none text-indigo-400" x-text="'-32.5 dB'"></span>
                    </div>
                </div>
            </div>

            <!-- SVG Visualization: Heavy Duty Grid -->
            <div class="flex-grow flex items-center justify-center p-20 relative overflow-hidden group/viz">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_110%,rgba(79,70,229,0.2)_0%,transparent_60%)] animate-pulse"></div>
                
                <svg viewBox="0 0 600 600" class="w-full h-full drop-shadow-[0_0_80px_rgba(79,70,229,0.4)] transition-all duration-1000 filter brightness-110" preserveAspectRatio="xMidYMid meet">
                    <!-- Floor Matrix -->
                    <rect x="50" y="50" width="500" height="500" fill="none" stroke="rgba(99,102,241,0.15)" stroke-width="1" />
                    
                    <defs>
                        <pattern id="industrialGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(99,102,241,0.2)" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect x="50" y="50" width="500" height="500" fill="url(#industrialGrid)" />

                    <!-- Power Rack Silhouette -->
                    <template x-if="powerRack">
                        <g transform="translate(300, 100)" class="transition-all duration-500">
                            <rect x="0" y="0" width="12" height="350" fill="#4f46e5" class="animate-pulse" />
                            <rect x="180" y="0" width="12" height="350" fill="#4f46e5" />
                            <rect x="0" y="20" width="192" height="12" fill="#4f46e5" />
                            <!-- Barbell -->
                            <rect x="-40" y="150" width="272" height="8" fill="#94a3b8" rx="4" />
                            <circle cx="-40" cy="154" r="15" fill="#1e293b" />
                            <circle cx="232" cy="154" r="15" fill="#1e293b" />
                        </g>
                    </template>

                    <!-- Cardio Cluster -->
                    <template x-if="cardioEnabled">
                        <g transform="translate(80, 300)" class="transition-all duration-500">
                            <rect x="0" y="30" width="100" height="180" fill="#1e1b4b" rx="4" />
                            <rect x="10" y="40" width="80" height="160" fill="#4f46e5" opacity="0.4" />
                            <rect x="10" y="10" width="80" height="40" fill="#4f46e5" rx="2" />
                        </g>
                    </template>

                    <!-- HUD Markers -->
                    <circle cx="300" cy="300" r="2" fill="#4f46e5" class="animate-ping" />
                    <text x="310" y="305" fill="#4f46e5" font-size="8" font-family="monospace" class="font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">Center-O-Mass</text>
                </svg>
            </div>

            <!-- Heavy Performance HUD Footer -->
            <div class="p-10 grid grid-cols-3 gap-1 relative z-10 bg-black/50 backdrop-blur-2xl border-t border-white/5">
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-indigo-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 font-black italic leading-none">Operating Volume</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none" x-text="area * 3.5 + ' m³ / AIR'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-indigo-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 font-black italic leading-none">Mat Density</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none" x-text="'1100 kg/m³'"></span>
                </div>
                <div class="bg-black/40 p-6 rounded-2xl border-b-4 border-indigo-500/40">
                    <span class="text-[8px] text-slate-500 uppercase font-black block tracking-widest mb-1 font-black italic leading-none">AI Mirror HUD</span>
                    <span class="text-3xl text-white font-black italic tracking-tighter uppercase leading-none transition-colors" :class="aiMirror ? 'text-indigo-400' : 'text-slate-600'" x-text="aiMirror ? 'SYNCED' : 'OFFLINE'"></span>
                </div>
            </div>
        </div>

        <!-- Controls: Stronghold Fabrication -->
        <div class="bg-slate-900/40 p-12 lg:p-16 flex flex-col rounded-[3rem] border border-white/5 relative overflow-hidden backdrop-blur-3xl">
            
            <div class="mb-16 relative group/header">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-none group-hover/header:text-indigo-400 transition-colors">Strength-Core</h3>
                        <p class="text-[10px] text-indigo-500 font-extrabold uppercase tracking-[0.5em] mt-4 opacity-80 italic flex items-center space-x-3">
                             <span class="w-12 h-[1px] bg-indigo-500 animate-pulse"></span>
                             <span>Elite Performance Hub</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-grow space-y-12 overflow-y-auto pr-4 custom-scrollbar">
                
                <!-- Floor Composition Logic -->
                <div class="p-8 bg-black/40 rounded-[2.5rem] border border-white/5 relative overflow-hidden group/opt hover:bg-black/60 transition-all">
                    <div class="flex justify-between items-center mb-8 pl-2 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                        <span class="text-[11px] text-indigo-500 font-black uppercase tracking-[0.3em]">01. Kinetic Foundation</span>
                        <div class="w-16 h-[1px] bg-indigo-500/30"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <template x-for="tech in ['Recycled Rubber 20mm', 'Performance Turf', 'Heavy Duty Vinyl', 'Force Mat DX']">
                            <button @click="flooring = tech" 
                                    :class="flooring === tech ? 'bg-indigo-700 text-white shadow-[0_0_30px_rgba(79,70,229,0.5)] border-indigo-500 pl-6' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 pl-4'"
                                    class="py-5 rounded-2xl border font-black italic uppercase text-[10px] transition-all tracking-widest flex items-center space-x-3 text-left font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                                <span class="w-1.5 h-1.5 rounded-full" :class="flooring === tech ? 'bg-white animate-pulse' : 'bg-slate-700'"></span>
                                <span x-text="tech"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Parameters: LOA & Displacement Equivalent -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                    <div class="space-y-6">
                        <div class="flex justify-between items-end mb-4 border-l-4 border-indigo-500/40 pl-5 transition-all hover:pl-8">
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none font-black italic tracking-tighter leading-none italic leading-none">Facility Scope Area</span>
                            <span class="text-4xl font-black text-white italic tracking-tighter leading-none italic font-black leading-none uppercase leading-none font-black uppercase tracking-tighter leading-none font-bold font-italic font-black transition-colors" x-text="area + ' m²'"></span>
                        </div>
                        <div class="relative py-4">
                            <input type="range" x-model="area" min="15" max="300" step="5" 
                               class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-600 transition-all hover:accent-indigo-400">
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block leading-none mb-4 font-black italic tracking-tighter leading-none italic leading-none">Station Topology</span>
                        <div class="grid grid-cols-1 gap-3">
                            <button @click="cardioEnabled = !cardioEnabled" 
                                    :class="cardioEnabled ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/50 pr-6' : 'bg-white/5 text-slate-500 border-white/5 pr-4'"
                                    class="py-3 px-4 border rounded-2xl text-[10px] font-black uppercase italic transition-all flex justify-between items-center font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                                <span>Cardio Cluster</span>
                                <span class="text-[8px] px-2 py-0.5 rounded border" :class="cardioEnabled ? 'border-indigo-500 text-indigo-400' : 'border-slate-800 text-slate-700'" x-text="cardioEnabled ? 'ONLINE' : 'STBY'"></span>
                            </button>
                            <button @click="powerRack = !powerRack" 
                                    :class="powerRack ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/50 pr-6' : 'bg-white/5 text-slate-500 border-white/5 pr-4'"
                                    class="py-3 px-4 border rounded-2xl text-[10px] font-black uppercase italic transition-all flex justify-between items-center font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                                <span>Power Rack Matrix</span>
                                <span class="text-[8px] px-2 py-0.5 rounded border" :class="powerRack ? 'border-indigo-500 text-indigo-400' : 'border-slate-800 text-slate-700'" x-text="powerRack ? 'ONLINE' : 'STBY'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Augmentation -->
                <div class="grid grid-cols-2 gap-6 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black">
                    <button @click="aiMirror = !aiMirror" 
                            class="p-6 rounded-[2rem] border transition-all flex flex-col items-center text-center space-y-4"
                            :class="aiMirror ? 'bg-indigo-600/20 border-indigo-500 shadow-[0_15px_30px_rgba(79,70,229,0.2)]' : 'bg-black/40 border-white/5 hover:bg-black/60'">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center border transition-all" :class="aiMirror ? 'bg-indigo-500 border-indigo-400 text-white' : 'bg-white/5 border-white/10 text-slate-600 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </div>
                        <span class="text-[11px] font-black italic tracking-widest text-white uppercase leading-none font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic">AI Mirror HUD</span>
                    </button>
                    
                    <button @click="soundSystem = !soundSystem" 
                            class="p-6 rounded-[2rem] border transition-all flex flex-col items-center text-center space-y-4"
                            :class="soundSystem ? 'bg-indigo-600/20 border-indigo-500 shadow-[0_15px_30px_rgba(79,70,229,0.2)]' : 'bg-black/40 border-white/5 hover:bg-black/60'">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center border transition-all" :class="soundSystem ? 'bg-indigo-500 border-indigo-400 text-white' : 'bg-white/5 border-white/10 text-slate-600 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                        </div>
                        <span class="text-[11px] font-black italic tracking-widest text-white uppercase leading-none font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic font-black uppercase">Sonos High-Fidelity</span>
                    </button>
                </div>
            </div>

            <!-- Heavy Asset Valuation HUD -->
            <div class="mt-16 p-12 bg-indigo-700 rounded-[3.5rem] shadow-[0_40px_80px_rgba(79,70,229,0.4)] relative overflow-hidden group/total">
                <div class="absolute inset-0 bg-[linear-gradient(45deg,transparent_25%,rgba(255,255,255,0.15)_50%,transparent_75%)] bg-[length:250%_250%] animate-[shimmer_6s_infinite_linear]"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-10">
                    <div>
                        <span class="text-[11px] font-black text-indigo-950 uppercase tracking-[0.5em] block mb-3 font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic font-black uppercase tracking-widest leading-none font-black font-black italic leading-none uppercase tracking-tighter leading-none italic underline leading-none uppercase font-black italic leading-none font-black font-black italic leading-none uppercase leading-none font-black leading-none uppercase leading-none font-bold font-italic transition-all duration-300">Iron System Valuation</span>
                        <div class="text-6xl font-black text-slate-950 italic tracking-tighter bg-black/10 px-6 py-2 rounded-2xl font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black font-black uppercase tracking-tighter leading-none italic font-black tracking-tighter leading-none italic leading-none font-black italic font-black uppercase tracking-widest leading-none font-black font-black italic leading-none uppercase tracking-tighter leading-none italic underline leading-none uppercase font-black italic leading-none font-black font-black italic leading-none uppercase leading-none font-black leading-none uppercase leading-none shadow-inner" x-text="formatValue(totalCost)"></div>
                    </div>
                    <button class="bg-slate-950 text-white px-16 py-7 rounded-[2.5rem] font-black uppercase text-sm tracking-[0.3em] shadow-2xl hover:bg-indigo-900 transition-all hover:scale-105 active:scale-95 italic font-black">
                        Lock Deployment
                    </button>
                </div>
            </div>
            
            <p class="mt-10 text-[9px] text-slate-600 italic uppercase tracking-[0.6em] text-center italic font-black italic uppercase italic leading-none font-black italic leading-none uppercase leading-none font-black leading-none italic font-black h-4 uppercase font-black italic leading-none uppercase font-black italic font-black uppercase tracking-tighter leading-none italic font-black leading-none font-black font-black italic leading-none uppercase leading-none font-black leading-none uppercase leading-none leading-relaxed transition-all duration-300">
                Performance Optimized. Structural harmonics verified via Strength-Core.
            </p>
        </div>
    </div>
</div>
