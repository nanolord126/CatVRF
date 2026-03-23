@props(['template'])

<div x-data="visualAiArchitect(@js($template))" class="relative group p-8 bg-slate-900/90 backdrop-blur-3xl rounded-[2.5rem] border border-white/10 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.6)] overflow-hidden transition-all duration-500">
    <!-- AI Neural Background Effect -->
    <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,#4f46e5,transparent_70%)] opacity-30"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-150"></div>
    </div>

    <div class="flex flex-col xl:flex-row gap-12 relative z-10">
        <!-- AI Vision Port (Left Side) -->
        <div class="w-full xl:w-2/3 space-y-6">
            <div class="relative aspect-[16/10] bg-black/80 rounded-[2rem] border-2 border-dashed border-indigo-500/30 flex items-center justify-center overflow-hidden group/canvas">
                <!-- Camera Feed / Upload Placeholder -->
                <template x-if="!image">
                    <div class="text-center space-y-4">
                        <div class="relative inline-block">
                            <div class="absolute inset-0 bg-indigo-500 blur-2xl opacity-20 animate-pulse"></div>
                            <svg class="relative w-24 h-24 text-indigo-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <p class="text-indigo-200/60 font-black italic uppercase tracking-[0.2em] text-sm">Drop Room Photo or URL</p>
                        <button @click="$refs.fileInput.click()" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full font-bold transition-all shadow-xl shadow-indigo-500/20 active:scale-95">
                            SELECT SOURCE
                        </button>
                        <input type="file" x-ref="fileInput" class="hidden" @change="handleUpload">
                    </div>
                </template>

                <!-- AI Overlay Results -->
                <template x-if="image">
                    <div class="relative w-full h-full">
                        <img :src="image" class="w-full h-full object-cover">
                        <!-- AI Scanning Animation -->
                        <div x-show="processing" class="absolute inset-0 bg-gradient-to-b from-transparent via-indigo-500/40 to-transparent h-20 w-full animate-scan"></div>
                        
                        <!-- Detected Objects (Dynamic SVGs) -->
                        <template x-for="obj in detections" :key="obj.id">
                            <div class="absolute border-2 border-indigo-400 bg-indigo-400/10 backdrop-blur-sm rounded-lg group/obj pointer-events-auto"
                                 :style="`left: \${obj.x}%; top: \${obj.y}%; width: \${obj.w}%; height: \${obj.h}%`"
                                 @click="selectObject(obj)">
                                <span class="absolute -top-6 left-0 bg-indigo-500 text-white text-[9px] font-black px-2 py-0.5 rounded italic whitespace-nowrap" x-text="obj.label"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- AI Intelligence Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-1">Confidence</span>
                    <span class="text-xl font-black text-indigo-400 italic" x-text="confidence + '%'"></span>
                </div>
                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-1">Dimensions</span>
                    <span class="text-xl font-black text-white italic" x-text="measurements.estimated"></span>
                </div>
                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-1">Anomalies</span>
                    <span class="text-xl font-black text-emerald-400 italic">0</span>
                </div>
                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center">
                    <span class="text-[9px] text-slate-500 uppercase font-black block tracking-widest mb-1">Model</span>
                    <span class="text-xl font-black text-white italic">GPT-4o/V</span>
                </div>
            </div>
        </div>

        <!-- AI Control Unit (Right Side) -->
        <div class="w-full xl:w-1/3 flex flex-col space-y-8">
            <div class="space-y-2">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-1 bg-indigo-500"></div>
                    <span class="text-indigo-400 font-black italic uppercase tracking-widest text-xs">Architect Neural Module</span>
                </div>
                <h1 class="text-5xl font-black text-white italic tracking-tighter uppercase leading-[0.85]">
                    AI <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500">Visual</span><br>Estimator
                </h1>
            </div>

            <div class="space-y-6">
                <!-- Select Task -->
                <div class="space-y-4">
                    <span class="text-slate-500 text-[10px] uppercase font-bold italic tracking-widest pl-2 block">Режим анализа</span>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="setMode('furniture')" :class="mode === 'furniture' ? 'bg-indigo-600 border-indigo-400 shadow-[0_0_20px_rgba(79,70,229,0.3)]' : 'bg-white/5 border-white/10'" 
                                class="p-4 rounded-2xl border text-left transition-all group/btn">
                            <span class="text-white font-bold italic block leading-none">Furniture</span>
                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-tighter mt-1 block">Detect + Replace</span>
                        </button>
                        <button @click="setMode('renovation')" :class="mode === 'renovation' ? 'bg-indigo-600 border-indigo-400 shadow-[0_0_20px_rgba(79,70,229,0.3)]' : 'bg-white/5 border-white/10'" 
                                class="p-4 rounded-2xl border text-left transition-all group/btn">
                            <span class="text-white font-bold italic block leading-none">Surfaces</span>
                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-tighter mt-1 block">Auto-Area Calc</span>
                        </button>
                    </div>
                </div>

                <!-- AI Style Transfer -->
                <div class="p-6 bg-white/5 rounded-[2rem] border border-white/10 space-y-4">
                    <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest">Generative Style Transfer</span>
                    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
                        <template x-for="style in styles">
                            <button @click="selectedStyle = style.id" 
                                    :class="selectedStyle === style.id ? 'border-indigo-500 bg-indigo-500/20' : 'border-white/10 bg-white/5'"
                                    class="flex-shrink-0 px-4 py-2 rounded-xl border transition-all">
                                <span class="text-white text-xs font-bold italic whitespace-nowrap" x-text="style.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Technical Output -->
                <div class="bg-indigo-600/10 p-8 rounded-[2rem] border border-indigo-500/20 space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-slate-500 text-[9px] uppercase font-black block tracking-widest mb-1">Project Estimation</span>
                            <span class="text-4xl font-black text-white italic tracking-tighter" x-text="formatPrice(totalPrice)"></span>
                        </div>
                        <div class="bg-indigo-500/20 p-2 rounded-lg border border-indigo-500/30">
                            <svg class="w-6 h-6 text-indigo-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button @click="processAi()" class="py-5 bg-white text-black rounded-[1.5rem] font-black italic tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-white/10 flex items-center justify-center space-x-2">
                    <svg x-show="processing" class="animate-spin h-5 w-5 text-black" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="processing ? 'ANALYZING...' : 'RUN AI ANALYSIS'"></span>
                </button>
                <button @click="exportSpecs()" class="py-5 bg-black border border-white/20 text-white rounded-[1.5rem] font-black italic tracking-widest hover:bg-white/5 transition-all">
                    GET BOQ PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Grid Layer (Reference only) -->
    <div class="absolute inset-x-8 top-8 h-[2px] bg-gradient-to-r from-transparent via-indigo-500/20 to-transparent"></div>
</div>

<style>
@keyframes scan {
    0% { transform: translateY(-100%); }
    100% { transform: translateY(400%); }
}
.animate-scan {
    animation: scan 3s infinite linear;
}
</style>
