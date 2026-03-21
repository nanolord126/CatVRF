<div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Мои бронирования</h2>

    <!-- Filter -->
    <div class="mb-6 flex gap-2">
        <button 
            wire:click="$set('filterStatus', null)"
            class="px-4 py-2 rounded {{ !$filterStatus ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}"
        >
            Все
        </button>
        @foreach (['confirmed', 'checked_in', 'checked_out', 'cancelled'] as $status)
            <button 
                wire:click="$set('filterStatus', '{{ $status }}')"
                class="px-4 py-2 rounded {{ $filterStatus === $status ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}"
            >
                {{ ucfirst($status) }}
            </button>
        @endforeach
    </div>

    @if ($isLoading)
        <div class="text-center py-8">
            <p class="text-gray-600">Загрузка...</p>
        </div>
    @elseif (count($bookings) === 0)
        <div class="text-center py-8">
            <p class="text-gray-600">У вас нет бронирований</p>
        </div>
    @else
        <!-- Bookings List -->
        <div class="space-y-4">
            @foreach ($bookings as $booking)
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center mb-4">
                        <!-- Hotel Info -->
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Отель</p>
                            <p class="font-semibold text-gray-800">{{ $booking['hotel_name'] }}</p>
                        </div>

                        <!-- Room Info -->
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Номер</p>
                            <p class="font-semibold text-gray-800">№ {{ $booking['room_number'] }}</p>
                        </div>

                        <!-- Dates -->
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Даты</p>
                            <p class="font-semibold text-gray-800">
                                {{ $booking['check_in'] }} – {{ $booking['check_out'] }}
                                <span class="text-gray-500">({{ $booking['nights'] }} ночей)</span>
                            </p>
                        </div>

                        <!-- Price -->
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Сумма</p>
                            <p class="font-semibold text-gray-800">{{ $booking['total_price'] }} ₽</p>
                        </div>

                        <!-- Status -->
                        <div class="text-right">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $booking['status'] === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $booking['status'] === 'checked_out' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $booking['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            ">
                                {{ $booking['status_label'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    @if ($booking['status'] === 'confirmed')
                        <button 
                            wire:click="cancelBooking({{ $booking['id'] }})"
                            class="text-red-600 hover:text-red-800 font-medium text-sm"
                        >
                            Отменить бронирование
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
