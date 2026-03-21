<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Отслеживание заказа</h2>

    <div class="bg-black/20 p-6 rounded-lg mb-6">
        <p class="text-gray-300 mb-2">Заказ: <span class="text-amber-400 font-bold">{{ $orderId }}</span></p>
        <p class="text-gray-300">Готово примерно через: <span class="text-green-400 font-bold">{{ $estimatedTime }}</span></p>
    </div>

    <!-- Временная шкала -->
    <div class="space-y-4">
        @foreach ($timeline as $event)
            <div class="flex items-center">
                <div class="flex flex-col items-center">
                    <div class="w-4 h-4 rounded-full {{ $event['completed'] ? 'bg-green-500' : 'bg-gray-600' }}"></div>
                    @if (!$loop->last)
                        <div class="w-1 h-12 {{ $event['completed'] ? 'bg-green-500' : 'bg-gray-600' }}"></div>
                    @endif
                </div>
                <div class="ml-4">
                    <p class="text-white font-semibold capitalize">{{ str_replace('_', ' ', $event['status']) }}</p>
                    <p class="text-gray-400 text-sm">{{ $event['time'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <button class="w-full mt-6 bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
        Связаться с поддержкой
    </button>
</div>
