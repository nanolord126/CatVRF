<div class="p-8 rounded-3xl bg-gradient-to-br from-indigo-900/80 to-blue-900/80 backdrop-blur-xl border border-white/20 shadow-3xl text-white">
    <div class="flex items-center mb-6">
        <div class="p-3 bg-blue-500/20 rounded-2xl mr-4">
            <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h2 class="text-2xl font-bold">AI Smile Constructor 2026</h2>
            <p class="text-blue-200 text-sm">Узнайте, как улучшить вашу улыбку прямо сейчас</p>
        </div>
    </div>

    @if(!$analysis)
        <div class="space-y-6">
            <div 
                x-data="{ isDragging: false }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; @this.upload('photo', $event.dataTransfer.files[0])"
                class="relative border-2 border-dashed rounded-2xl py-12 px-4 transition-all"
                :class="isDragging ? 'border-blue-400 bg-blue-400/10' : 'border-white/20 bg-white/5'"
            >
                @if ($photo)
                    <div class="flex flex-col items-center">
                        <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-xl border-4 border-blue-500 mb-4 shadow-xl">
                        <button wire:click="analyze" class="bg-blue-500 hover:bg-blue-400 px-8 py-3 rounded-xl font-bold transition-all transform hover:scale-105">
                            Запустить анализ
                        </button>
                    </div>
                @else
                    <div class="text-center">
                        <p class="text-slate-300 mb-4">Перетащите фото лица или нажмите кнопку</p>
                        <input type="file" wire:model="photo" class="hidden" id="smile-photo">
                        <label for="smile-photo" class="cursor-pointer bg-white/10 hover:bg-white/20 px-6 py-2 rounded-lg text-sm font-semibold transition-all">
                            Выбрать файл
                        </label>
                    </div>
                @endif
                
                <div wire:loading wire:target="photo" class="absolute inset-0 bg-slate-900/80 flex items-center justify-center rounded-2xl">
                    <span class="animate-pulse text-blue-400 font-bold">Загрузка...</span>
                </div>
            </div>
            
            <p class="text-[10px] text-slate-400 text-center uppercase tracking-widest">
                Обработка данных по ФЗ-152. Ваши фото не сохраняются в открытом доступе.
            </p>
        </div>
    @else
        <div class="space-y-6 animate-fade-in">
            <div class="p-6 bg-white/5 rounded-2xl border border-white/10">
                <h3 class="font-bold text-lg mb-4 text-blue-300">Результаты анализа:</h3>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($analysis['analysis']['findings'] ?? ['Симметрия: Ок', 'Тон: А2'] as $finding)
                        <div class="bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                            <span class="text-xs text-slate-400 block mb-1">Параметр</span>
                            <span class="text-white font-medium">{{ $finding }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-6 bg-blue-500/10 rounded-2xl border border-blue-500/30">
                <h3 class="font-bold text-lg mb-2 text-white">Рекомендуемые услуги:</h3>
                <ul class="space-y-2">
                    @foreach($analysis['recommendations'] ?? [] as $rec)
                        <li class="flex items-center text-sm">
                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2"></span>
                            {{ $rec['service_name'] ?? 'Профессиональная чистка' }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <button wire:click="resetWidget" class="text-sm text-blue-300 hover:text-white underline transition-all">
                Новый анализ
            </button>
        </div>
    @endif

    <div wire:loading wire:target="analyze" class="fixed inset-0 bg-slate-900/90 flex flex-col items-center justify-center z-50">
        <div class="w-20 h-20 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-xl font-bold text-white animate-pulse">Искусственный интеллект изучает вашу улыбку...</p>
    </div>
</div>
