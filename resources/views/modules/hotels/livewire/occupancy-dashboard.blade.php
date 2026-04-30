<div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Occupancy Dashboard</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="previousDay" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    ←
                </button>
                <span class="text-lg font-medium">{{ $date->format('M j, Y') }}</span>
                <button wire:click="nextDay" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    →
                </button>
                <button wire:click="today" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Today
                </button>
            </div>
        </div>

        <!-- Key Stats -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Occupancy Rate</div>
                <div class="text-2xl font-bold text-blue-600">{{ $stats['occupancy_rate'] }}%</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Today's Revenue</div>
                <div class="text-2xl font-bold text-green-600">₽{{ number_format($stats['today_revenue'], 0) }}</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Check-ins Today</div>
                <div class="text-2xl font-bold text-purple-600">{{ $stats['check_ins_today'] }}</div>
            </div>
            <div class="bg-orange-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Check-outs Today</div>
                <div class="text-2xl font-bold text-orange-600">{{ $stats['check_outs_today'] }}</div>
            </div>
        </div>

        <!-- Room Status -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white border rounded-lg p-4">
                <div class="text-sm text-gray-600">Available Rooms</div>
                <div class="text-2xl font-bold text-gray-900">{{ $stats['available_rooms'] }}</div>
            </div>
            <div class="bg-white border rounded-lg p-4">
                <div class="text-sm text-gray-600">Dirty Rooms</div>
                <div class="text-2xl font-bold text-red-600">{{ $stats['dirty_rooms'] }}</div>
            </div>
            <div class="bg-white border rounded-lg p-4">
                <div class="text-sm text-gray-600">Maintenance</div>
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['maintenance_rooms'] }}</div>
            </div>
            <div class="bg-white border rounded-lg p-4">
                <div class="text-sm text-gray-600">Staying Guests</div>
                <div class="text-2xl font-bold text-gray-900">{{ $stats['staying_guests'] }}</div>
            </div>
        </div>

        <!-- Occupancy Trend Chart -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold mb-4">7-Day Occupancy Trend</h3>
            <div class="h-64 bg-gray-50 rounded-lg flex items-end justify-around p-4">
                @foreach($occupancyTrend as $day)
                    <div class="flex flex-col items-center">
                        <div class="w-8 bg-blue-500 rounded-t" style="height: {{ $day['occupancy_rate'] * 1.5 }}px;"></div>
                        <div class="text-xs mt-2">{{ substr($day['date'], 5) }}</div>
                        <div class="text-xs font-medium">{{ $day['occupancy_rate'] }}%</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Forecast -->
        <div>
            <h3 class="text-lg font-semibold mb-4">14-Day Forecast</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confirmed</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($forecast as $day)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $day['date'] }}</td>
                                <td class="px-4 py-2">{{ $day['confirmed_occupancy'] }}%</td>
                                <td class="px-4 py-2">{{ $day['pending_occupancy'] }}%</td>
                                <td class="px-4 py-2">{{ $day['available_rooms'] }}</td>
                                <td class="px-4 py-2">₽{{ number_format($day['forecasted_revenue'], 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
