{{-- HRView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700">
            @foreach (['overview' => 'Обзор', 'leaves' => 'Отпуска', 'performance' => 'Оценка'] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                        'border-primary-600 text-primary-600' => $tab === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего сотрудников</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_employees'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">В отпуске</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['on_vacation'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ожидают решения</p>
                <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_requests'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Открытые вакансии</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['open_positions'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Employees List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Сотрудники</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Имя</th>
                            <th class="text-left pb-2">Должность</th>
                            <th class="text-left pb-2">Отдел</th>
                            <th class="text-left pb-2">Статус</th>
                            <th class="text-right pb-2">Дата найма</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $employee['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $employee['name'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ $employee['position'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ $employee['department'] ?? '–' }}</td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($employee['status'] ?? '') === 'active',
                                        'bg-gray-100 text-gray-700' => ($employee['status'] ?? '') !== 'active',
                                    ])>{{ $employee['status'] ?? '–' }}</span>
                                </td>
                                <td class="py-2 text-right text-gray-400">
                                    {{ \Carbon\Carbon::parse($employee['hired_at'] ?? now())->format('d.m.Y') }}
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
