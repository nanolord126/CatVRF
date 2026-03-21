<div class="space-y-4">
    <div class="h-96 overflow-y-auto border border-zinc-700/50 rounded-xl p-4 bg-zinc-900/40 backdrop-blur font-mono text-sm">
        @foreach ($messages ?? [] as $msg)
            <div class="mb-4 {{ $msg['role'] === 'user' ? 'text-right' : 'text-left' }}">
                <span class="inline-block px-3 py-2 rounded-lg {{ $msg['role'] === 'user' ? 'bg-fuchsia-600' : 'bg-zinc-800' }}">
                    {{ $msg['content'] }}
                </span>
            </div>
        @endforeach
    </div>
    <div class="flex gap-2">
        <input type="text" class="flex-grow bg-zinc-800 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-fuchsia-500" placeholder="Введите сообщение...">
        <button class="bg-fuchsia-600 px-6 py-3 rounded-lg font-bold hover:bg-fuchsia-500">ОТПРАВИТЬ</button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        @foreach ([
            '3 дня (490 руб, 30 зап/день)',
            '7 дней (990 руб, 35 зап/день)',
            '14 дней (1790 руб, 40 зап/день)',
            '30 дней (2990 руб, 50 зап/день)'
        ] as $ПЛАН)
            <div class="p-4 bg-zinc-800/80 rounded-xl border border-white/5 hover:border-fuchsia-500 active:scale-95 transition cursor-pointer">
                <span class="text-xs uppercase font-black text-fuchsia-400">ПЛАН</span>
                <p class="text-sm font-bold mt-1">{{ $ПЛАН }}</p>
            </div>
        @endforeach
    </div>
</div>
