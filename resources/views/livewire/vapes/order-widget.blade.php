<div class="p-6 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl shadow-2xl glassmorphism-vape">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white tracking-widest uppercase">
            Vape Vertical Order (18+)
        </h2>
        <div class="px-3 py-1 rounded-full text-xs font-semibold {{ $isAgeVerified ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' }}">
            {{ $isAgeVerified ? 'VERIFIED ADULT' : 'RESTRICTED (ESIA REQUIRED)' }}
        </div>
    </div>

    <!-- Список товаров в корзине -->
    <div class="space-y-4 mb-8">
        @foreach($items as $item)
            <div class="p-4 bg-white/5 border border-white/10 rounded-xl flex items-center justify-between transition-all hover:bg-white/10 group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-lg flex items-center justify-center text-indigo-300">
                        <i class="fas fa-{{ $item['type'] === 'device' ? 'microchip' : 'tint' }}"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white group-hover:text-indigo-300 transition-colors">{{ $item['name'] }}</div>
                        <div class="text-xs text-gray-400">Qty: {{ $item['qty'] }} • {{ $item['type'] }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold text-white tracking-tighter">{{ number_format($item['price_kopecks'] / 100, 2, '.', ' ') }} ₽</div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-widest">Price (Vat 20% Incl)</div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Итоговая сумма и кнопка -->
    <div class="pt-6 border-t border-white/10">
        <div class="flex items-center justify-between mb-4">
            <span class="text-gray-400 text-sm">Cart Total ({{ count($items) }} items)</span>
            <span class="text-xl font-black text-white tracking-tighter">{{ number_format($amountKopecks / 100, 2, '.', ' ') }} ₽</span>
        </div>

        @if($isAgeVerified)
            <button wire:click="submitOrder" 
                    wire:loading.attr="disabled"
                    class="w-full py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-black text-sm uppercase tracking-[0.2em] rounded-xl shadow-lg transition-all active:scale-95 disabled:opacity-50">
                <span wire:loading.remove>COMPLETE VAPE ORDER</span>
                <span wire:loading>INITIATING MARKING SESSION...</span>
            </button>
        @else
            <a href="/vapes/verify" 
               class="block w-full text-center py-4 bg-red-500 hover:bg-red-600 text-white font-black text-sm uppercase tracking-[0.2em] rounded-xl shadow-lg transition-all active:scale-95">
                VERIFY AGE VIA ESIA (GOSUSLUGI)
            </a>
            <p class="mt-3 text-[10px] text-gray-500 text-center leading-relaxed">
                * Продажа вейпов лицам младше 18 лет запрещена согласно ФЗ-15. <br>
                Подтверждение личности обязательно.
            </p>
        @endif
    </div>

    <!-- Correlation ID (Audit Tooltip) -->
    <div class="mt-6 text-[8px] text-gray-600 text-center uppercase tracking-widest opacity-30 hover:opacity-100 transition-opacity">
        Audit Trace ID: {{ $correlationId }}
    </div>
</div>
