<div class="glassmorphism rounded-lg p-6 hover:shadow-lg transition">
    <div class="space-y-3">
        <h3 class="text-lg font-semibold text-white">{{ $serviceName }}</h3>
        
        <p class="text-sm text-gray-300">Провайдер: {{ $providerName }}</p>
        
        <div class="flex justify-between items-center">
            <span class="text-2xl font-bold text-amber-400">₽{{ number_format($price / 100, 2) }}</span>
            <div class="flex items-center">
                <span class="text-yellow-400">★</span>
                <span class="ml-1 text-sm text-gray-300">{{ $rating }}/5</span>
            </div>
        </div>

        <button 
            wire:click="bookService"
            class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition"
        >
            Забронировать
        </button>
    </div>
</div>
