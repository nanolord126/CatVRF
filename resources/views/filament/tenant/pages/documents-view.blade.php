{{-- DocumentsView — CatVRF 2026 --}}
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
                @foreach (['all' => 'Все', 'contracts' => 'Договоры', 'invoices' => 'Счёта', 'acts' => 'Акты'] as $key => $label)
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
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего документов</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_documents'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Размер</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($stats['total_size'] ?? 0, 2, '.', ' ') }} MB
                </p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Договоры</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['contracts'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Счёта</p>
                <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['invoices'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Documents List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Документы</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Название</th>
                            <th class="text-left pb-2">Тип</th>
                            <th class="text-right pb-2">Размер</th>
                            <th class="text-left pb-2">Статус</th>
                            <th class="text-right pb-2">Дата загрузки</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($documents as $document)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $document['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $document['name'] ?? '–' }}</td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-blue-100 text-blue-700' => ($document['type'] ?? '') === 'contract',
                                        'bg-green-100 text-green-700' => ($document['type'] ?? '') === 'invoice',
                                        'bg-purple-100 text-purple-700' => ($document['type'] ?? '') === 'act',
                                        'bg-gray-100 text-gray-700' => !in_array($document['type'] ?? '', ['contract', 'invoice', 'act']),
                                    ])>{{ $document['type'] ?? '–' }}</span>
                                </td>
                                <td class="py-2 text-right text-gray-500">
                                    {{ number_format(($document['size'] ?? 0) / 1024, 2, '.', ' ') }} KB
                                </td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($document['status'] ?? '') === 'verified',
                                        'bg-yellow-100 text-yellow-700' => ($document['status'] ?? '') === 'pending',
                                        'bg-gray-100 text-gray-700' => !in_array($document['status'] ?? '', ['verified', 'pending']),
                                    ])>{{ $document['status'] ?? '–' }}</span>
                                </td>
                                <td class="py-2 text-right text-gray-400">
                                    {{ \Carbon\Carbon::parse($document['created_at'] ?? now())->format('d.m.Y H:i') }}
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
