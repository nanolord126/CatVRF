<div class="glassmorphism rounded-lg p-6 hover:shadow-lg transition">
    <img src="{{ $imageUrl }}" alt="{{ $productName }}" class="w-full h-48 object-cover rounded-lg mb-4">
    
    <div class="space-y-3">
        <h3 class="text-lg font-semibold text-white">{{ $productName }}</h3>
        
        <div class="flex justify-between items-center">
            <span class="text-2xl font-bold text-amber-400">₽{{ number_format($price / 100, 2) }}</span>
            <div class="flex items-center">
                <span class="text-yellow-400">★</span>
                <span class="ml-1 text-sm text-gray-300">{{ $rating }}/5</span>
            </div>
        </div>

        <button 
            wire:click="addToCart"
            class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-2 px-4 rounded-lg transition"
        >
            В корзину
        </button>
    </div>
</div>
