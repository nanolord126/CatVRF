{{-- MarketingView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2">
            @foreach (['active' => 'Активные', 'completed' => 'Завершённые', 'all' => 'Все'] as $key => $label)
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

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Активные кампании</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active_campaigns'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Бюджет</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($stats['total_spend'] ?? 0, 0, '.', ' ') }} ₽
                </p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Активные промокоды</p>
                <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['active_promos'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Конверсии (30 дн)</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['conversions'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Campaigns List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Маркетинговые кампании</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Название</th>
                            <th class="text-left pb-2">Канал</th>
                            <th class="text-right pb-2">Бюджет</th>
                            <th class="text-right pb-2">Расход</th>
                            <th class="text-left pb-2">Статус</th>
                            <th class="text-right pb-2">ROI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $campaign['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $campaign['name'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ $campaign['channel'] ?? '–' }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    {{ number_format(($campaign['budget'] ?? 0) / 100, 0, '.', ' ') }} ₽
                                </td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    {{ number_format(($campaign['spent'] ?? 0) / 100, 0, '.', ' ') }} ₽
                                </td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($campaign['status'] ?? '') === 'active',
                                        'bg-gray-100 text-gray-700' => ($campaign['status'] ?? '') === 'completed',
                                        'bg-yellow-100 text-yellow-700' => ($campaign['status'] ?? '') === 'paused',
                                    ])>{{ $campaign['status'] ?? '–' }}</span>
                                </td>
                                <td class="py-2 text-right font-medium @class([
                                    'text-green-600' => ($campaign['roi'] ?? 0) > 0,
                                    'text-red-600' => ($campaign['roi'] ?? 0) <= 0,
                                ])">{{ number_format($campaign['roi'] ?? 0, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-400">Нет данных</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
