<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Отслеживание такси</h2>

    <div class="bg-black/20 p-6 rounded-lg mb-6">
        <div class="mb-4">
            <p class="text-gray-300 mb-2">Водитель: <span class="text-white font-bold">{{ $driverName }}</span></p>
            <p class="text-gray-300">Автомобиль: <span class="text-amber-400 font-bold">{{ $vehicleLicense }}</span></p>
        </div>
        <p class="text-green-400 text-lg font-bold">Прибытие за {{ $eta }}</p>
    </div>

    <!-- Карта (симуляция) -->
    <div class="w-full h-80 bg-gray-800 rounded-lg mb-6 flex items-center justify-center">
        <div class="text-center">
            <p class="text-gray-400">📍 Водитель на маршруте</p>
            <p class="text-gray-500 text-sm mt-2">Широта: {{ $driverLat }} | Долгота: {{ $driverLon }}</p>
        </div>
    </div>

    <button 
        wire:click="callDriver"
        class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:shadow-lg transition"
    >
        ☎️ Позвонить водителю
    </button>

    <button class="w-full mt-3 px-4 py-2 bg-red-600/20 text-red-400 font-bold rounded-lg hover:bg-red-600/40 transition">
        Отменить поездку
    </button>
</div>
