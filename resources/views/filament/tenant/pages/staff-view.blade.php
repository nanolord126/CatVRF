{{-- StaffView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 flex-wrap">
            @foreach (['active' => 'Активные', 'inactive' => 'Неактивные', 'on_vacation' => 'В отпуске', 'archived' => 'В архиве'] as $key => $label)
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
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Активных</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">В отпуске</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['on_vacation'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">В архиве</p>
                <p class="mt-1 text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $stats['archived'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Общая выручка</p>
                <p class="mt-1 text-lg font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($stats['total_revenue'] ?? 0, 0, '.', ' ') }} ₽
                </p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ср. эффективность</p>
                <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($stats['avg_efficiency'] ?? 0, 1) }}%
                </p>
            </div>
        </div>

        {{-- Top Performers Row --}}
        @if (!empty($topPerformers))
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">🏆 Топ performers</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach ($topPerformers as $index => $performer)
                        <div class="flex items-center gap-3 p-3 rounded-lg @if($index === 0) bg-yellow-50 dark:bg-yellow-900/20 @elseif($index === 1) bg-gray-50 dark:bg-gray-800 @elseif($index === 2) bg-orange-50 dark:bg-orange-900/20 @else bg-gray-50 dark:bg-gray-800/50 @endif">
                            <div class="relative">
                                @if(!empty($performer['video_avatar_url']))
                                    <video
                                        src="{{ $performer['video_avatar_url'] }}"
                                        alt="{{ $performer['full_name'] }}"
                                        class="w-12 h-12 rounded-full object-cover"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                    ></video>
                                @else
                                    <img
                                        src="{{ $performer['photo_url'] ?? '/images/default-avatar.png' }}"
                                        alt="{{ $performer['full_name'] }}"
                                        class="w-12 h-12 rounded-full object-cover"
                                    >
                                @endif
                                <span class="absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center text-xs font-bold rounded-full
                                    @if($index === 0) bg-yellow-400 text-white
                                    @elseif($index === 1) bg-gray-400 text-white
                                    @elseif($index === 2) bg-orange-400 text-white
                                    @else bg-gray-300 text-gray-600 @endif">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $performer['full_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $performer['position'] ?? '–' }}</p>
                                <p class="text-xs font-bold text-primary-600">{{ number_format($performer['total_revenue'] ?? 0, 0, '.', ' ') }} ₽</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Staff List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Список сотрудников</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Сортировка:</span>
                    <select wire:model.live="sortBy" class="text-xs border rounded px-2 py-1">
                        <option value="created_at">Дата добавления</option>
                        <option value="total_revenue_generated">Выручка</option>
                        <option value="customer_satisfaction_score">Оценка клиентов</option>
                        <option value="full_name">Имя</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2"></th>
                            <th class="text-left pb-2 cursor-pointer hover:text-gray-700" wire:click="setSort('full_name')">ФИО</th>
                            <th class="text-left pb-2">Должность</th>
                            <th class="text-left pb-2">Отдел</th>
                            <th class="text-left pb-2">Роль</th>
                            <th class="text-right pb-2 cursor-pointer hover:text-gray-700" wire:click="setSort('total_revenue_generated')">Выручка</th>
                            <th class="text-right pb-2 cursor-pointer hover:text-gray-700" wire:click="setSort('customer_satisfaction_score')">Оценка</th>
                            <th class="text-right pb-2">Эффективность</th>
                            <th class="text-left pb-2">Статус</th>
                            <th class="text-right pb-2">Дата найма</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($staff as $employee)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3">
                                    <img
                                        src="{{ $employee['photo_url'] ?? '/images/default-avatar.png' }}"
                                        alt="{{ $employee['full_name'] }}"
                                        class="w-8 h-8 rounded-full object-cover"
                                    >
                                </td>
                                <td class="py-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $employee['full_name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $employee['email'] ?? '' }}</p>
                                    </div>
                                </td>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ $employee['position'] ?? '–' }}</td>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ $employee['department'] ?? '–' }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-red-100 text-red-700' => ($employee['role'] ?? '') === 'owner',
                                        'bg-yellow-100 text-yellow-700' => ($employee['role'] ?? '') === 'manager',
                                        'bg-blue-100 text-blue-700' => ($employee['role'] ?? '') === 'accountant',
                                        'bg-gray-100 text-gray-700' => ($employee['role'] ?? '') === 'employee',
                                    ])>{{ match($employee['role'] ?? 'employee') {
                                        'owner' => 'Владелец',
                                        'manager' => 'Менеджер',
                                        'accountant' => 'Бухгалтер',
                                        default => 'Сотрудник',
                                    } }}</span>
                                </td>
                                <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                    {{ number_format($employee['total_revenue_generated'] ?? 0, 0, '.', ' ') }} ₽
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="font-medium @class([
                                            'text-green-600' => ($employee['customer_satisfaction_score'] ?? 0) >= 4,
                                            'text-yellow-600' => ($employee['customer_satisfaction_score'] ?? 0) >= 3 && ($employee['customer_satisfaction_score'] ?? 0) < 4,
                                            'text-red-600' => ($employee['customer_satisfaction_score'] ?? 0) < 3,
                                        ])">{{ number_format($employee['customer_satisfaction_score'] ?? 0, 1) }}</span>
                                        <span class="text-xs text-gray-400">/5</span>
                                    </div>
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div
                                                class="h-2 rounded-full @class([
                                                    'bg-green-500' => ($employee['efficiency_score'] ?? 0) >= 80,
                                                    'bg-yellow-500' => ($employee['efficiency_score'] ?? 0) >= 60 && ($employee['efficiency_score'] ?? 0) < 80,
                                                    'bg-red-500' => ($employee['efficiency_score'] ?? 0) < 60,
                                                ])"
                                                style="width: {{ min(100, $employee['efficiency_score'] ?? 0) }}%"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ number_format($employee['efficiency_score'] ?? 0, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($employee['status'] ?? '') === 'active',
                                        'bg-gray-100 text-gray-700' => ($employee['status'] ?? '') === 'inactive',
                                        'bg-blue-100 text-blue-700' => ($employee['status'] ?? '') === 'on_vacation',
                                        'bg-yellow-100 text-yellow-700' => ($employee['status'] ?? '') === 'probation',
                                        'bg-red-100 text-red-700' => ($employee['status'] ?? '') === 'archived',
                                    ])>{{ match($employee['status'] ?? 'active') {
                                        'active' => 'Активный',
                                        'inactive' => 'Неактивный',
                                        'on_vacation' => 'В отпуске',
                                        'probation' => 'Испытательный',
                                        'archived' => 'В архиве',
                                        default => '–',
                                    } }}</span>
                                </td>
                                <td class="py-3 text-right text-gray-500">{{ $employee['hired_at'] ?? '–' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-gray-400">
                                    <p class="text-sm">Нет сотрудников</p>
                                    <p class="text-xs mt-1">Добавьте первого сотрудника через кнопку выше</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
