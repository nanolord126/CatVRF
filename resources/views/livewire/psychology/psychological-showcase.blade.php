<div class="p-6 bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl text-white">
    <!-- Header -->
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2">Psychological Services 2026</h1>
            <p class="text-white/60">Find your best therapist with AI assistance</p>
        </div>
        
        <!-- AI Helper Toggle -->
        <button wire:click="$toggle('isAiMatching')" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 rounded-2xl transition-all font-bold shadow-lg shadow-indigo-500/30 flex items-center gap-2">
            <x-heroicon-o-sparkles class="w-5 h-5" />
            AI Therapy Matcher
        </button>
    </div>

    <!-- AI Matcher Sidebar / Modal Backdrop -->
    @if($isAiMatching || $aiPlan)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-slate-900 border border-indigo-500/30 w-full max-w-2xl rounded-[2.5rem] p-8 shadow-2xl shadow-indigo-500/20">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold flex items-center gap-3">
                    <span class="p-2 bg-indigo-500/20 rounded-lg"><x-heroicon-o-sparkles class="w-6 h-6 text-indigo-400" /></span>
                    Personalized AI Matching
                </h2>
                <button wire:click="resetAi" class="text-white/40 hover:text-white"><x-heroicon-o-x-mark class="w-6 h-6" /></button>
            </div>

            @if(!$aiPlan)
                <div class="space-y-6">
                    <p class="text-white/70">What brings you here today? Select all that apply:</p>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach(['Anxiety', 'Depression', 'Burnout', 'Family Issues', 'C-PTSD', 'Sleep Problems'] as $symptom)
                            <label class="flex items-center gap-3 p-4 bg-white/5 border border-white/10 rounded-2xl cursor-pointer hover:bg-white/10 transition-colors">
                                <input type="checkbox" wire:model="aiSymptoms" value="{{ strtolower($symptom) }}" class="w-5 h-5 rounded border-white/20 bg-transparent text-indigo-500">
                                <span>{{ $symptom }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button wire:click="startAiMatch" class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 rounded-2xl font-bold text-lg transition-all">
                        Analyze & Find Specialist
                    </button>
                </div>
            @else
                <div class="space-y-6 animate-fade-in">
                    <div class="p-6 bg-indigo-500/10 border border-indigo-500/20 rounded-3xl">
                        <h3 class="text-indigo-400 font-bold mb-2 uppercase tracking-widest text-sm">Suggested Strategy</h3>
                        <p class="text-xl font-medium">{{ $aiPlan['therapy_type'] }}</p>
                        <div class="mt-4 flex gap-4 text-sm text-white/60">
                            <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">{{ $aiPlan['suggested_duration'] }}</span>
                            <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">{{ $aiPlan['frequency'] }}</span>
                        </div>
                    </div>

                    <h3 class="font-bold text-lg px-2">Recommended Specialists ({{ $aiPlan['confidence_score'] * 100 }}% match)</h3>
                    <div class="space-y-3">
                        @foreach($aiPlan['matches'] as $match)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-500/20 rounded-full flex items-center justify-center font-bold text-indigo-400">
                                        {{ substr($match['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold">{{ $match['name'] }}</div>
                                        <div class="text-xs text-white/40">Similarity: {{ round($match['similarity_score'] * 100) }}%</div>
                                    </div>
                                </div>
                                <button wire:click="selectTherapist({{ $match['psychologist_id'] }})" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-sm transition-all">View Profile</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Main Listing -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach($psychologists as $therapist)
            <div class="group relative bg-white/5 border border-white/10 rounded-[2rem] p-6 hover:bg-white/10 transition-all hover:-translate-y-2 overflow-hidden shadow-xl shadow-black/20">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl transition-all group-hover:bg-indigo-500/20"></div>
                
                <div class="flex items-start justify-between mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-2xl font-black shadow-lg shadow-indigo-500/20">
                        {{ substr($therapist->full_name, 0, 1) }}
                    </div>
                    <div class="flex items-center gap-1 px-3 py-1 bg-amber-400/20 border border-amber-400/30 rounded-full text-amber-400 font-bold text-sm">
                        <x-heroicon-s-star class="w-4 h-4" />
                        {{ number_format($therapist->rating ?: 4.9, 1) }}
                    </div>
                </div>

                <h3 class="text-xl font-bold mb-1 truncate">{{ $therapist->full_name }}</h3>
                <p class="text-indigo-400 text-sm font-medium mb-4 truncate">{{ $therapist->specialization }}</p>
                
                <div class="flex flex-wrap gap-2 mb-6 h-14 overflow-hidden">
                    @foreach($therapist->therapy_types ?? [] as $type)
                        <span class="text-[10px] uppercase font-bold tracking-tighter px-2 py-0.5 bg-white/5 border border-white/10 rounded-md text-white/40">{{ $type }}</span>
                    @endforeach
                </div>

                <div class="flex items-end justify-between mt-auto">
                    <div>
                        <div class="text-[10px] text-white/40 uppercase font-black">Starts at</div>
                        <div class="text-2xl font-black">{{ number_format($therapist->base_price_per_hour / 100) }} ₽</div>
                    </div>
                    <button wire:click="selectTherapist({{ $therapist->id }})" class="p-3 bg-white hover:bg-white/90 text-slate-900 rounded-2xl transition-all shadow-lg shadow-white/10">
                        <x-heroicon-o-calendar class="w-6 h-6" />
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-12">
        {{ $psychologists->links() }}
    </div>
</div>
