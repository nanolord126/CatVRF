<div class="glassmorphism rounded-lg p-6">
    <h2 class="text-2xl font-bold text-white mb-6">Календарь доступных номеров</h2>

    <form class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-white font-semibold mb-2">Заезд:</label>
                <input 
                    type="date" 
                    wire:model="startDate"
                    wire:change="checkAvailability"
                    class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg"
                >
            </div>
            <div>
                <label class="block text-white font-semibold mb-2">Выезд:</label>
                <input 
                    type="date" 
                    wire:model="endDate"
                    wire:change="checkAvailability"
                    class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg"
                >
            </div>
        </div>
    </form>

    @if (!empty($availableRooms))
        <div class="space-y-4 mt-6">
            @foreach ($availableRooms as $room)
                <div class="bg-black/20 p-4 rounded-lg flex justify-between items-center">
                    <div>
                        <p class="text-white font-semibold">{{ $room['type'] }}</p>
                        <p class="text-amber-400">₽{{ number_format($room['price'] / 100, 2) }}/ночь</p>
                    </div>
                    <button 
                        wire:click="selectRoom({{ $room['id'] }})"
                        class="{{ $room['available'] ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 cursor-not-allowed' }} text-white font-bold py-2 px-6 rounded-lg transition"
                        {{ !$room['available'] ? 'disabled' : '' }}
                    >
                        {{ $room['available'] ? 'Выбрать' : 'Занято' }}
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
