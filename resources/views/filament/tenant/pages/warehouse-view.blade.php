{{-- WarehouseView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего складов</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_warehouses'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Общая ёмкость</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['total_capacity'] ?? 0, 0, '.', ' ') }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Использовано</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['used_capacity'] ?? 0, 0, '.', ' ') }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ожидают перемещения</p>
                <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_transfers'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Capacity Progress --}}
        @if (($stats['total_capacity'] ?? 0) > 0)
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Загрузка складов</h3>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                    <div
                        class="bg-primary-600 h-4 rounded-full transition-all duration-500"
                        style="width: {{ min(100, round((($stats['used_capacity'] ?? 0) / ($stats['total_capacity'] ?? 1)) * 100)) }}%"
                    ></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    {{ round((($stats['used_capacity'] ?? 0) / ($stats['total_capacity'] ?? 1)) * 100, 1) }}% заполнено
                </p>
            </div>
        @endif

        {{-- Warehouses List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Список складов</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Название</th>
                            <th class="text-left pb-2">Адрес</th>
                            <th class="text-right pb-2">Ёмкость</th>
                            <th class="text-right pb-2">Использовано</th>
                            <th class="text-right pb-2">Статус</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($warehouses as $warehouse)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $warehouse['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $warehouse['name'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ $warehouse['address'] ?? '–' }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">{{ $warehouse['capacity'] ?? 0 }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">{{ $warehouse['used_capacity'] ?? 0 }}</td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($warehouse['status'] ?? '') === 'active',
                                        'bg-gray-100 text-gray-700' => ($warehouse['status'] ?? '') !== 'active',
                                    ])>{{ $warehouse['status'] ?? '–' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-400">Нет данных</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
