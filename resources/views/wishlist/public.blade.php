@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-900 text-white p-8">
    <div class="max-w-4xl mx-auto backdrop-blur-xl bg-white/10 border border-white/20 rounded-3xl p-8 shadow-2xl">
        <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
            {{ $wishlist->title }}
        </h1>
        <p class="text-slate-400 mb-8">Помогите осуществить мечту (анонимно)</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($wishlist->items as $item)
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 transition-all hover:scale-[1.02]">
                <h3 class="text-xl font-semibold mb-2">{{ $item->product->name }}</h3>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-widest">Цена</p>
                        <p class="text-2xl font-mono">{{ number_format($item->price_at_addition, 2) }} ₽</p>
                    </div>
                    @if(!$item->is_fully_paid)
                    <button 
                        onclick="payFor('{{ $item->id }}')"
                        class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full font-bold hover:shadow-[0_0_20px_rgba(168,85,247,0.4)] transition-all"
                    >
                        Пополнить
                    </button>
                    @else
                    <span class="text-green-400 font-bold">Оплачено ✓</span>
                    @endif
                </div>
                <div class="mt-4 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full bg-cyan-400" style="width: {{ ($item->collected_amount / $item->price_at_addition) * 100 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function payFor(id) {
    fetch(`/wishlist/pay/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => window.location.href = data.url);
}
</script>
@endsection
