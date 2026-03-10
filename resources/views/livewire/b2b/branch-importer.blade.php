<div class="p-8 bg-black/10 backdrop-blur-3xl rounded-[3rem] border border-white/5 shadow-inner animate-in fade-in zoom-in duration-500">
    <div x-data="{ dragging: false }" 
         x-on:dragover.prevent="dragging = true" 
         x-on:dragleave.prevent="dragging = false"
         x-on:drop.prevent="dragging = false"
         class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-3xl p-12 transition-all"
         :class="dragging ? 'border-blue-500 bg-blue-500/5' : 'border-white/10'">
        
        <div class="mb-4 text-6xl drop-shadow-2xl">📁</div>
        <h3 class="text-xl font-bold mb-2">Импорт филиалов 2026</h3>
        <p class="text-slate-400 text-sm mb-6 text-center max-w-sm">Перетащите Excel-файл для автоматического создания филиалов, пользователей и настройки Multi-tenant изоляции.</p>
        
        <input type="file" wire:model="file" class="hidden" id="branchFile">
        <label for="branchFile" class="cursor-pointer px-10 py-4 bg-white text-black rounded-2xl font-black hover:scale-105 transition-transform active:scale-95">Выбрать файл</label>
        
        @if($file)
            <div class="mt-6 w-full max-w-xs animate-pulse">
                <button wire:click="startImport" class="w-full py-4 bg-blue-600 rounded-2xl font-bold shadow-lg shadow-blue-500/30">Запустить импорт</button>
            </div>
        @endif
    </div>
</div>
