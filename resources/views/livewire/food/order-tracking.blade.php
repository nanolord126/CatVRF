<div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow-lg">
    @if ($order)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                Заказ #{{ $order->id }}
            </h2>
            <p class="text-gray-600">
                Ресторан: <strong>{{ $order->restaurant->name }}</strong>
            </p>
        </div>

        <!-- Order Timeline -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">Статус заказа</h3>
            
            <div class="space-y-4">
                @foreach ($timelineEvents as $event)
                    <div class="flex items-start">
                        <!-- Timeline Dot -->
                        <div class="flex flex-col items-center mr-4">
                            <div class="w-8 h-8 bg-{{ $event['completed'] ? 'green' : 'gray' }}-200 rounded-full flex items-center justify-center">
                                @if ($event['completed'])
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                @endif
                            </div>
                            @if (!$loop->last)
                                <div class="w-0.5 h-8 bg-gray-200 my-2"></div>
                            @endif
                        </div>

                        <!-- Event Details -->
                        <div class="pt-1">
                            <p class="font-medium text-gray-800">{{ $event['title'] }}</p>
                            <p class="text-sm text-gray-500">{{ $event['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Order Items -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Состав заказа</h3>
            
            <div class="border border-gray-200 rounded-lg divide-y">
                @foreach ($order->items as $item)
                    <div class="p-4 flex justify-between">
                        <div>
                            <p class="font-medium text-gray-800">{{ $item->dish->name }}</p>
                            <p class="text-sm text-gray-500">{{ $item->quantity }} шт.</p>
                        </div>
                        <p class="font-medium text-gray-800">{{ number_format($item->price / 100, 2, '.', '') }} ₽</p>
                    </div>
                @endforeach
                
                <!-- Total -->
                <div class="p-4 bg-gray-50 flex justify-between font-semibold text-lg">
                    <span>Итого:</span>
                    <span>{{ number_format($order->total_price / 100, 2, '.', '') }} ₽</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        @if ($order->status === 'pending')
            <button 
                wire:click="cancelOrder"
                class="w-full bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition"
            >
                Отменить заказ
            </button>
        @endif

    @else
        <div class="text-center py-8">
            <p class="text-gray-600">Заказ не найден</p>
        </div>
    @endif
</div>
