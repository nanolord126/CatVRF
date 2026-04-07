<div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4 border-b border-gray-700 pb-2">Your Cart</h2>

    @if (empty($items))
        <p class="text-gray-400">Your cart is empty.</p>
    @else
        <div class="space-y-4">
            @foreach ($items as $id => $item)
                <div class="flex justify-between items-center bg-gray-700 p-3 rounded">
                    <div>
                        <p class="font-semibold">{{ $item['name'] }}</p>
                        <p class="text-sm text-gray-400">${{ number_format($item['price'], 2) }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <input type="number" wire:model.lazy="items.{{ $id }}.quantity" wire:change="updateQuantity('{{ $id }}', $event.target.value)" class="w-16 bg-gray-800 border border-gray-600 rounded text-center">
                        <button wire:click="removeItem('{{ $id }}')" class="text-red-500 hover:text-red-400">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 border-t border-gray-700 pt-4">
            <div class="flex justify-between">
                <p class="text-gray-400">Subtotal</p>
                <p>${{ number_format($subtotal, 2) }}</p>
            </div>
            <div class="flex justify-between">
                <p class="text-gray-400">Delivery Fee</p>
                <p>${{ number_format($deliveryFee, 2) }}</p>
            </div>
            <div class="flex justify-between font-bold text-lg mt-2">
                <p>Total</p>
                <p>${{ number_format($total, 2) }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-2">
            <button wire:click="checkout" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded transition duration-300">
                Checkout
            </button>
            <button wire:click="clearCart" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded transition duration-300">
                Clear Cart
            </button>
        </div>
    @endif
</div>
