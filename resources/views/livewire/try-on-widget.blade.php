<div class="try-on-widget p-8 max-w-sm mx-auto bg-slate-900 border border-white/10 rounded-[3rem] shadow-2xl backdrop-blur-3xl animate-in zoom-in-95 duration-500">
    <h3 class="text-2xl font-black text-white mb-4 text-center">Визуальная Примерка 2026</h3>
    <div class="relative w-full aspect-square bg-slate-800 rounded-3xl overflow-hidden border border-white/10">
        @if ($result_url)
            <img src="{{ $result_url }}" class="w-full h-full object-cover">
        @else
            <div class="flex flex-col items-center justify-center h-full opacity-40">
                <span class="text-6xl mb-4">🤳</span>
                <p class="text-sm font-medium">Загрузите селфи для примерки</p>
            </div>
        @endif
    </div>

    <div class="mt-6 space-y-4">
        <input type="file" wire:model="photo" class="hidden" id="photoInput">
        <label for="photoInput" class="block w-full text-center py-4 bg-white text-slate-950 rounded-2xl font-black cursor-pointer hover:scale-105 transition-transform active:scale-95 shadow-xl">Выбрать фото</label>
        @if ($photo && !$loading)
            <button wire:click="applyTryOn" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl font-bold shadow-lg shadow-blue-500/30">Применить AI</button>
        @endif
    </div>
</div>
