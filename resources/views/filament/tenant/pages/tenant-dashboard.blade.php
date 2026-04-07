{{-- Tenant Dashboard — CatVRF 2026 --}}
<x-filament-panels::page>
    <div
        wire:poll.30000ms="refresh"
        class="space-y-6"
    >
        {{-- Period Selector --}}
        <div class="flex items-center gap-2">
            @foreach (['today' => 'Сегодня', '7d' => '7 дней', '30d' => '30 дней', '90d' => '90 дней'] as $key => $label)
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

        {{-- KPI Cards Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {{-- GMV Today --}}
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">GMV сегодня</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($gmvToday, 0, '.', ' ') }} ₽
                </p>
                <p class="text-xs text-gray-400 mt-1">За месяц: {{ number_format($gmv30d, 0, '.', ' ') }} ₽</p>
            </div>

            {{-- Orders Today --}}
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Заказы сегодня</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $ordersToday }}</p>
                <p class="text-xs text-gray-400 mt-1">За месяц: {{ $orders30d }}</p>
            </div>

            {{-- New Users --}}
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Клиентов сегодня</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $newUsersToday }}</p>
                <p class="text-xs text-gray-400 mt-1">Уникальных покупателей</p>
            </div>

            {{-- AI Usage --}}
            <div class="fi-stats-overview-stat rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">AI-конструктор сегодня</p>
                <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $aiUsageToday }}</p>
                <p class="text-xs text-gray-400 mt-1">Анализов / дизайнов</p>
            </div>

            {{-- Wallet Balance --}}
            <div class="fi-stats-overview-stat col-span-2 rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Баланс кошелька</p>
                <p class="mt-1 text-3xl font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($walletBalance, 2, '.', ' ') }} ₽
                </p>
                <p class="text-xs text-gray-400 mt-1">Доступно для вывода</p>
            </div>
        </div>

        {{-- Widgets Row --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />

        {{-- Bottom Row: Top Verticals + Recent Orders --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Verticals --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Топ вертикали (30 дней)</h3>
                @forelse ($topVerticals as $v)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div>
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ ucfirst($v['vertical'] ?? '–') }}
                            </span>
                            <span class="ml-2 text-xs text-gray-400">{{ $v['cnt'] }} зак.</span>
                        </div>
                        <span class="text-sm font-bold text-primary-600">
                            {{ number_format(($v['gmv'] ?? 0) / 100, 0, '.', ' ') }} ₽
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Нет данных</p>
                @endforelse
            </div>

            {{-- Recent Orders --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Последние заказы</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400">
                                <th class="text-left pb-2">ID</th>
                                <th class="text-left pb-2">Статус</th>
                                <th class="text-left pb-2">Вертикаль</th>
                                <th class="text-right pb-2">Сумма</th>
                                <th class="text-right pb-2">Время</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-300">
                                        #{{ $order['id'] }}
                                    </td>
                                    <td class="py-1.5">
                                        <span @class([
                                            'px-1.5 py-0.5 rounded text-[10px] font-medium',
                                            'bg-green-100 text-green-700' => $order['status'] === 'completed',
                                            'bg-blue-100 text-blue-700'   => $order['status'] === 'processing',
                                            'bg-yellow-100 text-yellow-700' => $order['status'] === 'pending',
                                            'bg-red-100 text-red-700'     => $order['status'] === 'cancelled',
                                            'bg-gray-100 text-gray-700'   => !in_array($order['status'], ['completed', 'processing', 'pending', 'cancelled']),
                                        ])>{{ $order['status'] }}</span>
                                    </td>
                                    <td class="py-1.5 text-gray-500">{{ $order['vertical'] ?? '–' }}</td>
                                    <td class="py-1.5 text-right font-medium text-gray-800 dark:text-gray-200">
                                        {{ number_format(($order['total_amount'] ?? 0) / 100, 0, '.', ' ') }} ₽
                                    </td>
                                    <td class="py-1.5 text-right text-gray-400">
                                        {{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400">Нет заказов</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
