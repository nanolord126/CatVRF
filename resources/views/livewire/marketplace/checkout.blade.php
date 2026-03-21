<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Оформление заказа</h2>

    <form wire:submit.prevent="processPayment" class="space-y-6">
        <!-- Товары -->
        <div class="bg-black/20 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-white mb-4">Товары</h3>
            @foreach ($cart as $item)
                <div class="flex justify-between text-gray-300 mb-2">
                    <span>{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                    <span>₽{{ number_format($item['price'] * $item['quantity'] / 100, 2) }}</span>
                </div>
            @endforeach
        </div>

        <!-- Доставка -->
        <div class="bg-black/20 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-white mb-4">Доставка</h3>
            <div class="space-y-2">
                @foreach (['standard' => 'Стандартная (бесплатно)', 'express' => 'Экспресс (500 ₽)', 'same_day' => 'В день заказа (1000 ₽)'] as $type => $label)
                    <label class="flex items-center text-gray-300">
                        <input 
                            type="radio" 
                            name="delivery_type" 
                            value="{{ $type }}"
                            wire:change="setDeliveryType('{{ $type }}')"
                            class="mr-2"
                        > {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Оплата -->
        <div class="bg-black/20 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-white mb-4">Способ оплаты</h3>
            <div class="space-y-2">
                @foreach (['card' => 'Карта', 'wallet' => 'Кошелек', 'bank_transfer' => 'Банковский перевод'] as $method => $label)
                    <label class="flex items-center text-gray-300">
                        <input 
                            type="radio" 
                            name="payment_method" 
                            value="{{ $method }}"
                            wire:change="setPaymentMethod('{{ $method }}')"
                            class="mr-2"
                        > {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Итого -->
        <div class="border-t border-gray-600 pt-4">
            <div class="flex justify-between text-white text-lg mb-2">
                <span>Сумма товаров:</span>
                <span>₽{{ number_format((collect($cart)->sum(fn ($i) => $i['price'] * $i['quantity'])) / 100, 2) }}</span>
            </div>
            <div class="flex justify-between text-white text-lg mb-4">
                <span>Доставка:</span>
                <span>₽{{ number_format($deliveryPrice / 100, 2) }}</span>
            </div>
            <div class="flex justify-between text-amber-400 text-2xl font-bold mb-6">
                <span>Итого:</span>
                <span>₽{{ number_format($totalPrice / 100, 2) }}</span>
            </div>

            <button 
                type="submit"
                class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transition"
            >
                Оплатить
            </button>
        </div>
    </form>
</div>
