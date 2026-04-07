<div>
    <div class="bg-white p-4 rounded-lg shadow-lg mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                <input type="text" wire:model.defer="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('city') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="checkInDate" class="block text-sm font-medium text-gray-700">Check-in</label>
                <input type="date" wire:model.defer="checkInDate" id="checkInDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('checkInDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="checkOutDate" class="block text-sm font-medium text-gray-700">Check-out</label>
                <input type="date" wire:model.defer="checkOutDate" id="checkOutDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('checkOutDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="capacity" class="block text-sm font-medium text-gray-700">Guests</label>
                <input type="number" wire:model.defer="capacity" id="capacity" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('capacity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="search" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                Search
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($hotels as $hotel)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">{{ $hotel->getName() }}</h3>
                    <p class="text-gray-600 mb-2">{{ $hotel->getAddress()->getCity() }}, {{ $hotel->getAddress()->getStreet() }}</p>
                    <div class="flex items-center mb-4">
                        <span class="text-yellow-500">{{ str_repeat('★', round($hotel->getRating())) }}</span>
                        <span class="ml-2 text-gray-600">{{ $hotel->getRating() }}</span>
                    </div>
                    <p class="text-gray-700">{{ Str::limit($hotel->getDescription(), 100) }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
