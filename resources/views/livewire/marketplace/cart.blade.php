<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Корзина ({{ $itemCount }} товаров)</h2>

    @if (empty($items))
        <div class="text-center py-12">
            <p class="text-gray-300 text-lg">Ваша корзина пуста</p>
        </div>
    @else
        <div class="space-y-4 mb-6">
            @foreach ($items as $key => $item)
                <div class="flex justify-between items-center bg-black/20 p-4 rounded-lg">
                    <div>
                        <p class="text-white font-semibold">{{ $item['name'] }}</p>
                        <p class="text-gray-400 text-sm">₽{{ number_format($item['price'] / 100, 2) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input 
                            type="number" 
                            min="1" 
                            value="{{ $item['quantity'] }}"
                            wire:change="updateQuantity('{{ $key }}', $event.target.value)"
                            class="w-16 px-2 py-1 bg-gray-800 text-white rounded"
                        >
                        <button 
                            wire:click="removeItem('{{ $key }}')"
                            class="text-red-500 hover:text-red-700 font-bold"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-600 pt-4">
            <div class="flex justify-between mb-4">
                <span class="text-white text-lg">Итого:</span>
                <span class="text-amber-400 text-2xl font-bold">₽{{ number_format($totalPrice / 100, 2) }}</span>
            </div>

            <div class="flex gap-2">
                <button 
                    wire:click="checkout"
                    class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transition"
                >
                    Оформить заказ
                </button>
                <button 
                    wire:click="clearCart"
                    class="px-4 py-3 bg-red-600/20 text-red-400 font-bold rounded-lg hover:bg-red-600/40 transition"
                >
                    Очистить
                </button>
            </div>
        </div>
    @endif
</div>
