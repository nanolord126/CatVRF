{{-- InventoryView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Search and Filter --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Поиск по названию..."
                class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
            <div class="flex items-center gap-2">
                @foreach (['all' => 'Все', 'low_stock' => 'Мало на складе', 'out_of_stock' => 'Нет в наличии'] as $key => $label)
                    <button
                        wire:click="setFilter('{{ $key }}')"
                        @class([
                            'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                            'bg-primary-600 text-white' => $filter === $key,
                            'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' => $filter !== $key,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего товаров</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_items'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Мало на складе</p>
                <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['low_stock'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Нет в наличии</p>
                <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['out_of_stock'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Стоимость</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($stats['total_value'] ?? 0, 0, '.', ' ') }} ₽
                </p>
            </div>
        </div>

        {{-- Inventory List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Инвентарь</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Название</th>
                            <th class="text-left pb-2">Категория</th>
                            <th class="text-right pb-2">Количество</th>
                            <th class="text-right pb-2">Мин. количество</th>
                            <th class="text-right pb-2">Цена закупки</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($inventory as $item)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $item['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $item['name'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ $item['category'] ?? '–' }}</td>
                                <td class="py-2 text-right font-medium @class([
                                    'text-red-600' => $item['quantity'] <= ($item['min_quantity'] ?? 0),
                                    'text-gray-800 dark:text-gray-200' => $item['quantity'] > ($item['min_quantity'] ?? 0),
                                ])">{{ $item['quantity'] ?? 0 }}</td>
                                <td class="py-2 text-right text-gray-500">{{ $item['min_quantity'] ?? 0 }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    {{ number_format(($item['cost_price'] ?? 0) / 100, 2, '.', ' ') }} ₽
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
