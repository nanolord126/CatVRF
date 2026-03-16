<div class="relative w-full max-w-4xl mx-auto p-4 sm:p-8 text-white min-h-[500px]" x-data="{ isDragging: false }">
    <!-- Анимация конфетти при одобрении (Alpine + Canvas-confetti script) -->
    @if($approved)
    <div x-init="confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } })" class="absolute inset-0 pointer-events-none"></div>

    <div class="glass-banner animate-bounce p-6 rounded-2xl border border-green-400/30 bg-green-500/10 backdrop-blur-xl flex items-center justify-between">
        <div class="flex items-center gap-4">
            <span class="text-4xl">🎉</span>
            <div>
                <h3 class="text-xl font-bold text-green-300">Платформа одобрена!</h3>
                <p class="text-sm opacity-80">Ваша комиссия снижена до 10% на 2 года.</p>
            </div>
        </div>
        <div class="hidden sm:block">
            <span class="px-4 py-2 rounded-full bg-green-500/20 border border-green-500/40 text-xs font-mono uppercase tracking-widest animate-pulse">
                Active 2026-2028
            </span>
        </div>
    </div>
    @endif

    @if(!$submitted)
    <div class="glass-card mt-8 p-8 relative overflow-hidden backdrop-blur-2xl bg-white/5 border border-white/10 rounded-3xl shadow-2xl transition-all hover:shadow-cyan-500/10">
        <!-- Декоративные пятна на фоне -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-cyan-500/20 blur-[80px] rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/10 blur-[80px] rounded-full"></div>

        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-5xl font-black tracking-tight mb-4 bg-gradient-to-r from-white via-cyan-200 to-white bg-clip-text text-transparent">
                Подтвердите переход к нам
            </h1>
            <p class="text-lg opacity-60 max-w-lg mx-auto">Получите эксклюзивную ставку 10%. Мы верим в ваш бизнес и готовы поддержать на старте в нашей экосистеме.</p>
        </div>

        <form wire:submit.prevent="submitRequest" class="space-y-8">
            <div class="space-y-4">
                <label class="block text-sm font-medium text-cyan-300/80 uppercase tracking-widest ml-1">Откуда переходите?</label>
                <div class="relative group">
                    <select wire:model="platform" 
                            class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 appearance-none focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all text-lg backdrop-blur-md">
                        <option value="" class="bg-[#1a1c23]">Выберите платформу...</option>
                        @foreach($platformOptions as $opt)
                            <option value="{{ $opt }}" class="bg-[#1a1c23]">{{ $opt }}</option>
                        @endforeach
                        <option value="Другое" class="bg-[#1a1c23]">Другое</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-6 text-white/40">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                @if($platform === 'Другое')
                <input wire:model.live="otherPlatformName" type="text" placeholder="Введите название..." 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-cyan-500/50 transition-all">
                @endif
            </div>

            <!-- Drag-and-Drop Area -->
            <div class="space-y-4">
                <label class="block text-sm font-medium text-cyan-300/80 uppercase tracking-widest ml-1">Доказательства (Договор/Скриншот)</label>
                <div class="relative group cursor-pointer"
                     :class="isDragging ? 'border-cyan-400 bg-cyan-500/10' : 'border-white/10'"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="isDragging = false"
                     onclick="document.getElementById('file-upload').click()">
                    
                    <input type="file" wire:model.live="files" id="file-upload" class="hidden" multiple>

                    <div class="border-2 border-dashed border-white/20 rounded-2xl p-12 text-center transition-all group-hover:border-white/40">
                        @if(!$files)
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center animate-pulse">
                                <svg class="w-8 h-8 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <p class="text-white/60">Перетащите файлы сюда или кликните для выбора</p>
                        </div>
                        @else
                        <div class="flex flex-wrap gap-4 justify-center">
                            @foreach($files as $file)
                            <div class="w-20 h-20 rounded-xl bg-white/10 flex items-center justify-center overflow-hidden border border-white/20">
                                <img src="{{ $file->temporaryUrl() }}" class="object-cover w-full h-full opacity-60">
                            </div>
                            @endforeach
                            <div class="w-20 h-20 rounded-xl border border-dashed border-white/20 flex items-center justify-center text-xl text-white/40">+</div>
                        </div>
                        @endif
                    </div>

                    <!-- Progress Bar (Simulation via Livewire upload) -->
                    <div wire:loading wire:target="files" class="absolute bottom-0 left-0 right-0 h-1 bg-cyan-500 animate-pulse rounded-full overflow-hidden"></div>
                </div>
            </div>

            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="w-full bg-gradient-to-r from-cyan-600 to-blue-700 hover:from-cyan-500 hover:to-blue-600 py-6 rounded-2xl font-bold text-xl transition-all transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3 shadow-xl">
                <span wire:loading.remove>Подтвердить переход</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Отправка...
                </span>
            </button>
        </form>
    </div>
    @else
    <!-- Pending State (Таймер 24 часа) -->
    @if(!$approved)
    <div class="glass-card mt-12 p-12 text-center rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl animate-fade-in">
        <div class="mb-8">
            <div class="w-24 h-24 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-cyan-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h2 class="text-3xl font-bold mb-4">Заявка отправлена!</h2>
            <p class="text-lg opacity-60">Агент безопасности уже проверяет ваши данные.</p>
        </div>
        
        <div class="flex flex-col items-center gap-2 p-6 bg-white/5 rounded-2xl border border-white/5 border-dashed">
            <span class="text-xs font-mono uppercase tracking-[0.3em] opacity-40">Ориентировочное время проверки</span>
            <div class="text-4xl font-mono font-black text-white/90">23:59:45</div>
        </div>
        
        <div class="mt-12">
            <button onclick="window.location.reload()" class="px-8 py-3 rounded-xl border border-white/10 hover:bg-white/5 transition-all opacity-40 hover:opacity-100 italic">Обновить статус</button>
        </div>
    </div>
    @endif
    @endif

    <style>
        .glass-card { box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glass-card:hover { transform: translateY(-5px); }
        .animate-fade-in { animation: fadeIn 0.8s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <!-- Подключаем скрипт конфетти для полной магии -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</div>
