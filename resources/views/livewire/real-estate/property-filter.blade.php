<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Фильтр объектов</h2>

    <form class="space-y-6">
        <!-- Тип недвижимости -->
        <div>
            <label class="block text-white font-semibold mb-2">Тип:</label>
            <select wire:model="propertyType" wire:change="applyFilters" class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg">
                <option value="">Все</option>
                <option value="apartment">Квартира</option>
                <option value="house">Дом</option>
                <option value="commercial">Коммерческое</option>
            </select>
        </div>

        <!-- Цена -->
        <div>
            <label class="block text-white font-semibold mb-2">Цена: ₽{{ number_format($priceMin / 100, 0) }} - ₽{{ number_format($priceMax / 100, 0) }}</label>
            <input 
                type="range" 
                min="0" 
                max="100000000" 
                wire:model="priceMax"
                wire:change="applyFilters"
                class="w-full"
            >
        </div>

        <!-- Площадь -->
        <div>
            <label class="block text-white font-semibold mb-2">Площадь: {{ $areaMin }}м² - {{ $areaMax }}м²</label>
            <input 
                type="range" 
                min="0" 
                max="500" 
                wire:model="areaMax"
                wire:change="applyFilters"
                class="w-full"
            >
        </div>

        <!-- Район -->
        <div>
            <label class="block text-white font-semibold mb-2">Район:</label>
            <input 
                type="text" 
                wire:model="district"
                wire:change="applyFilters"
                placeholder="Введите район"
                class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg"
            >
        </div>

        <div class="flex gap-2">
            <button 
                type="button"
                wire:click="applyFilters"
                class="flex-1 bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition"
            >
                Применить
            </button>
            <button 
                type="button"
                wire:click="resetFilters"
                class="px-4 py-2 bg-gray-700 text-gray-300 font-bold rounded-lg hover:bg-gray-600 transition"
            >
                Сбросить
            </button>
        </div>
    </form>

    @if (!empty($filteredProperties))
        <div class="mt-6 space-y-4">
            @foreach ($filteredProperties as $property)
                <div class="bg-black/20 p-4 rounded-lg">
                    <p class="text-white font-semibold">{{ $property['name'] }}</p>
                    <p class="text-amber-400 font-bold">₽{{ number_format($property['price'] / 100, 0) }}</p>
                    <p class="text-gray-400 text-sm">Площадь: {{ $property['area'] }}м²</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
