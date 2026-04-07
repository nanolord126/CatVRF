@php
    use Illuminate\Support\Carbon;
@endphp

<x-filament-panels::page>

    {{-- Реал-тайм обновление через polling --}}
    <div wire:poll.10000ms="refresh">

        {{-- Заголовок со статусом --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Мониторинг активен · обновление каждые 10 сек</span>
            </div>
            <span class="text-xs text-gray-400">{{ now()->format('d.m.Y H:i:s') }}</span>
        </div>

        {{-- Метрики за сегодня --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <div class="rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 p-4">
                <p class="text-xs text-red-600 dark:text-red-400 font-medium uppercase tracking-wide">🔴 Заблокировано</p>
                <p class="text-3xl font-bold text-red-700 dark:text-red-300 mt-1">{{ $criticalCount }}</p>
                <p class="text-xs text-red-400 mt-1">за сегодня</p>
            </div>

            <div class="rounded-2xl bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900 p-4">
                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium uppercase tracking-wide">🟠 На проверку</p>
                <p class="text-3xl font-bold text-orange-700 dark:text-orange-300 mt-1">{{ $highCount }}</p>
                <p class="text-xs text-orange-400 mt-1">за сегодня</p>
            </div>

            <div class="rounded-2xl bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-900 p-4">
                <p class="text-xs text-yellow-600 dark:text-yellow-400 font-medium uppercase tracking-wide">🟡 Предупреждения</p>
                <p class="text-3xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">{{ $warningCount }}</p>
                <p class="text-xs text-yellow-400 mt-1">за сегодня</p>
            </div>

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">🛡 Всего заблокировано</p>
                <p class="text-3xl font-bold text-gray-700 dark:text-gray-200 mt-1">{{ $blockedToday }}</p>
                <p class="text-xs text-gray-400 mt-1">сегодня</p>
            </div>
        </div>

        {{-- Виджеты (графики + статистика) --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />

        {{-- Лента последних событий --}}
        <div class="mt-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Последние события</h2>
                <span class="text-xs text-gray-400">топ 20 за сегодня</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Тип</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">User ID</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">ML Score</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Решение</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">IP</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Время</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($latestEvents as $event)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $event['operation_type'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $event['user_id'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @php $score = (float) ($event['ml_score'] ?? 0); @endphp
                                    <span class="font-medium {{ $score > 0.85 ? 'text-red-600' : ($score > 0.65 ? 'text-orange-500' : 'text-gray-500') }}">
                                        {{ number_format($score, 3) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ match($event['decision'] ?? '') {
                                            'block'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            'review' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                            default  => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                        } }}">
                                        {{ match($event['decision'] ?? '') {
                                            'block'  => '🔴 block',
                                            'review' => '🟠 review',
                                            default  => '🟢 allow',
                                        } }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $event['ip_address'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400">
                                    {{ isset($event['created_at']) ? Carbon::parse($event['created_at'])->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Событий не обнаружено</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
