<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header Strategy 2026: Glassmorphism -->
    <div class="mb-10 p-8 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl">
        <h1 class="text-4xl font-extrabold text-white mb-4">
            Стоматологические клиники рядом с вами
        </h1>
        <p class="text-blue-100 text-lg">
            Найдите лучших специалистов и запишитесь на прием за пару кликов. AI-подбор и гарантия качества 2026.
        </p>
        
        <!-- Filters Area -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input 
                wire:model.live.debounce.300ms="search"
                type="text" 
                placeholder="Поиск по названию..." 
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-blue-200 focus:ring-2 focus:ring-blue-400 outline-none transition-all"
            >
            
            <select 
                wire:model.live="radius"
                class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 outline-none transition-all"
            >
                <option value="5" class="bg-slate-800">В радиусе 5 км</option>
                <option value="15" class="bg-slate-800">В радиусе 15 км</option>
                <option value="30" class="bg-slate-800">Всей области</option>
            </select>

            <label class="flex items-center space-x-3 cursor-pointer group">
                <input type="checkbox" wire:model.live="isEmergencyOnly" class="hidden">
                <div class="w-6 h-6 border-2 border-white/20 rounded flex items-center justify-center transition-all group-hover:border-red-400 {{ $isEmergencyOnly ? 'bg-red-500 border-red-500' : '' }}">
                    @if($isEmergencyOnly)
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    @endif
                </div>
                <span class="text-white font-medium">Только круглосуточные</span>
            </label>

            <div class="flex items-center justify-end">
                <span class="text-blue-200 text-sm">Найдено: {{ $clinics->count() }} объектов</span>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($clinics as $clinic)
            <div 
                wire:key="{{ $clinic->uuid }}"
                class="group relative overflow-hidden rounded-3xl bg-slate-900/50 border border-white/10 hover:border-blue-500/50 transition-all duration-500 transform hover:-translate-y-2"
            >
                <!-- Image Overlay -->
                <div class="h-48 bg-gradient-to-br from-blue-600/20 to-purple-600/20 flex items-center justify-center">
                    @if($clinic->metadata['emergency'] ?? false)
                        <span class="absolute top-4 right-4 bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg pulse">
                            SOS: ЕСТЬ ПРИЕМ
                        </span>
                    @endif
                    <svg class="w-20 h-20 text-blue-400/30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 12h3v10h14V12h3L12 2z"/></svg>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-white group-hover:text-blue-400 transition-colors">
                            {{ $clinic->name }}
                        </h3>
                        <div class="flex items-center bg-white/5 px-2 py-1 rounded-lg">
                            <span class="text-yellow-400 font-bold mr-1">★</span>
                            <span class="text-white text-sm">{{ number_format($clinic->rating ?? 4.5, 1) }}</span>
                        </div>
                    </div>
                    
                    <p class="text-slate-400 text-sm mb-4 line-clamp-2">
                        {{ $clinic->metadata['address'] ?? 'Адрес уточняется' }}
                    </p>

                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($clinic->tags ?? ['Диагностика', 'Имплантация'] as $tag)
                            <span class="text-[10px] uppercase tracking-wider font-bold bg-blue-500/10 text-blue-400 px-2 py-1 rounded border border-blue-500/20">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>

                    <button 
                        wire:click="selectClinic({{ $clinic->id }})"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-900/40"
                    >
                        Записаться онлайн
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- No Results -->
    @if($clinics->isEmpty())
        <div class="text-center py-20 bg-white/5 rounded-3xl border border-dashed border-white/10">
            <svg class="w-20 h-20 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 20l9-5-9-5-9 5 9 5z"/></svg>
            <h2 class="text-2xl font-bold text-slate-400">Ничего не найдено</h2>
            <p class="text-slate-500">Попробуйте изменить параметры поиска или радиус</p>
        </div>
    @endif
</div>
