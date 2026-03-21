<div class="grid grid-cols-1 md:grid-cols-3 gap-8 py-10 px-4">
    @foreach ($products as $product)
        <div class="group relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl overflow-hidden shadow-2xl transition-all duration-500 hover:-translate-y-4 hover:rotate-2 hover:scale-105 perspective-1000">
            <div class="h-64 overflow-hidden relative">
                <img src="{{ $product->images[0] ?? '' }}" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-700" alt="{{ $product->name }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
            </div>
            <div class="p-6 text-white bg-black/40">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-widest bg-fuchsia-600 rounded-full shadow-[0_0_15px_rgba(217,70,239,0.5)]">
                        {{ $product->type }}
                    </span>
                    <span class="text-xs text-white/50">{{ $product->stock }} в наличии</span>
                </div>
                <h3 class="text-2xl font-black mb-1 drop-shadow-lg uppercase tracking-tight">{{ $product->name }}</h3>
                <p class="text-3xl font-light text-fuchsia-400 font-mono mb-4">{{ number_format($product->price, 0, '.', ' ') }} ₽</p>
                <button class="w-full py-4 bg-white text-black font-black uppercase tracking-widest text-xs rounded-xl hover:bg-fuchsia-400 hover:text-white transition-all transform active:scale-95 shadow-[0_10px_20px_rgba(0,0,0,0.3)]">
                    В корзину
                </button>
            </div>
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-fuchsia-500/20 blur-3xl group-hover:scale-150 transition-all duration-700"></div>
        </div>
    @endforeach
</div>
