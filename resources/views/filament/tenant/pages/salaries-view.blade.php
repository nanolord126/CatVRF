{{-- SalariesView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div wire:poll.30000ms="refresh" class="space-y-6">
        {{-- Period Selector --}}
        <div class="flex items-center gap-2">
            @foreach (['current_month' => 'Текущий месяц', 'last_month' => 'Прошлый месяц'] as $key => $label)
                <button
                    wire:click="setPeriod('{{ $key }}')"
                    @class([
                        'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                        'bg-primary-600 text-white' => $period === $key,
                        'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' => $period !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Выплачено</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($stats['total_paid'] ?? 0, 0, '.', ' ') }} ₽
                </p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ожидают выплаты</p>
                <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_payments'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Всего сотрудников</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_staff'] ?? 0 }}</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Средняя зарплата</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($stats['avg_salary'] ?? 0, 0, '.', ' ') }} ₽
                </p>
            </div>
        </div>

        {{-- Salaries List --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Выплаты зарплат</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">ID</th>
                            <th class="text-left pb-2">Сотрудник</th>
                            <th class="text-left pb-2">Период</th>
                            <th class="text-right pb-2">Сумма</th>
                            <th class="text-left pb-2">Статус</th>
                            <th class="text-right pb-2">Дата выплаты</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($salaries as $payment)
                            <tr>
                                <td class="py-2 font-mono text-gray-600 dark:text-gray-300">#{{ $payment['id'] ?? '' }}</td>
                                <td class="py-2 font-medium text-gray-800 dark:text-gray-200">{{ $payment['staff_name'] ?? '–' }}</td>
                                <td class="py-2 text-gray-500">{{ \Carbon\Carbon::parse($payment['period'] ?? now())->format('m.Y') }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                                    {{ number_format(($payment['amount'] ?? 0) / 100, 0, '.', ' ') }} ₽
                                </td>
                                <td class="py-2">
                                    <span @class([
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700' => ($payment['status'] ?? '') === 'paid',
                                        'bg-yellow-100 text-yellow-700' => ($payment['status'] ?? '') === 'pending',
                                        'bg-gray-100 text-gray-700' => !in_array($payment['status'] ?? '', ['paid', 'pending']),
                                    ])>{{ $payment['status'] ?? '–' }}</span>
                                </td>
                                <td class="py-2 text-right text-gray-400">
                                    {{ $payment['paid_at'] ? \Carbon\Carbon::parse($payment['paid_at'])->format('d.m.Y') : '–' }}
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
