<x-filament-widgets::widget>
    <x-filament::section>
        <h2 class="text-lg font-bold mb-4">Bookings Calendar (Current Month)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3">Booking Details</th>
                        <th class="px-4 py-3">Check In</th>
                        <th class="px-4 py-3">Check Out</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3 font-medium">{{ $event['title'] }}</td>
                            <td class="px-4 py-3">{{ $event['start'] }}</td>
                            <td class="px-4 py-3">{{ $event['end'] }}</td>
                            <td class="px-4 py-3">{{ $event['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-center text-gray-500">No bookings this month</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
