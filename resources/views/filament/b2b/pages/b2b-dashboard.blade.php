{{-- B2B Dashboard — CatVRF 2026 --}}
<x-filament-panels::page>
    <div
        wire:poll.60000ms="refresh"
        class="space-y-6"
    >
        {{-- Header: Business Info & Tier --}}
        <div class="flex items-center justify-between rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $businessName ?: 'Компания' }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Отсрочка платежа: <strong>{{ $paymentTermDays }} дней</strong>
                </p>
            </div>
            <div>
                @php
                    $tierColors = [
                        'standard' => 'bg-gray-100 text-gray-700',
                        'silver'   => 'bg-gray-200 text-gray-800',
                        'gold'     => 'bg-yellow-100 text-yellow-800',
                        'platinum' => 'bg-purple-100 text-purple-800',
                    ];
                    $color = $tierColors[$b2bTier] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $color }}">
                    {{ strtoupper($b2bTier) }}
                </span>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Оборот 30 дней</p>
                <p class="mt-1 text-2xl font-bold text-primary-600">
                    {{ number_format($gmv30d, 0, '.', ' ') }} ₽
                </p>
                <p class="text-xs text-gray-400 mt-1">За 90 дней: {{ number_format($gmv90d, 0, '.', ' ') }} ₽</p>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Активные заказы</p>
                <p class="mt-1 text-2xl font-bold text-blue-600">{{ $ordersActive }}</p>
                <p class="text-xs text-gray-400 mt-1">Выполнено за месяц: {{ $ordersPaid30d }}</p>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Кредитный лимит</p>
                <p class="mt-1 text-2xl font-bold {{ $creditAvailable > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($creditAvailable, 0, '.', ' ') }} ₽
                </p>
                <p class="text-xs text-gray-400 mt-1">Лимит: {{ number_format($creditLimit, 0, '.', ' ') }} ₽</p>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Использовано кредита</p>
                <p class="mt-1 text-2xl font-bold {{ $creditUsed > $creditLimit * 0.8 ? 'text-red-600' : 'text-gray-800 dark:text-gray-200' }}">
                    {{ number_format($creditUsed, 0, '.', ' ') }} ₽
                </p>
                @if ($creditLimit > 0)
                    <div class="mt-2 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-1.5 rounded-full {{ $creditUsed / $creditLimit > 0.8 ? 'bg-red-500' : 'bg-primary-500' }}"
                            style="width: {{ min(100, round($creditLimit > 0 ? ($creditUsed / $creditLimit) * 100 : 0)) }}%"
                        ></div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Widgets --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />

        {{-- Bottom: Top SKU + Recent Orders --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Products --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Топ SKU (30 дней)</h3>
                @forelse ($topProducts as $prod)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate max-w-[200px]">
                                {{ $prod['product_name'] ?? '#' . $prod['product_id'] }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $prod['qty'] }} шт.</p>
                        </div>
                        <span class="text-sm font-bold text-primary-600">
                            {{ number_format(($prod['total'] ?? 0) / 100, 0, '.', ' ') }} ₽
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Нет данных</p>
                @endforelse
            </div>

            {{-- Recent Orders --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Последние заказы</h3>
                    <a href="/b2b/b2b-orders" class="text-xs text-primary-600 hover:underline">Все заказы →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400">
                                <th class="text-left pb-2">ID</th>
                                <th class="text-left pb-2">Статус</th>
                                <th class="text-right pb-2">Сумма</th>
                                <th class="text-right pb-2">Дата</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-300">#{{ $order['id'] }}</td>
                                    <td class="py-1.5">
                                        <span @class([
                                            'px-1.5 py-0.5 rounded text-[10px] font-medium',
                                            'bg-green-100 text-green-700'   => $order['status'] === 'completed',
                                            'bg-blue-100 text-blue-700'     => $order['status'] === 'processing',
                                            'bg-yellow-100 text-yellow-700' => $order['status'] === 'pending',
                                            'bg-red-100 text-red-700'       => $order['status'] === 'cancelled',
                                            'bg-gray-100 text-gray-700'     => !in_array($order['status'], ['completed', 'processing', 'pending', 'cancelled']),
                                        ])>{{ $order['status'] }}</span>
                                    </td>
                                    <td class="py-1.5 text-right font-medium text-gray-800 dark:text-gray-200">
                                        {{ number_format(($order['total_amount'] ?? 0) / 100, 0, '.', ' ') }} ₽
                                    </td>
                                    <td class="py-1.5 text-right text-gray-400">
                                        {{ \Carbon\Carbon::parse($order['created_at'])->format('d.m H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-400">Нет заказов</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
