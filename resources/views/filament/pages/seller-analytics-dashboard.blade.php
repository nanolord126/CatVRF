<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Общая выручка</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($this->getViewData()['metrics']['total_revenue'], 2, ',', ' ') }} ₽
                        </p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка сегодня</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($this->getViewData()['metrics']['today_revenue'], 2, ',', ' ') }} ₽
                        </p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                        <x-heroicon-o-calendar class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка за месяц</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($this->getViewData()['metrics']['month_revenue'], 2, ',', ' ') }} ₽
                        </p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                        <x-heroicon-o-chart-pie class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Активные заказы</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $this->getViewData()['metrics']['active_orders'] }}
                        </p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                        <x-heroicon-o-shopping-cart class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Всего товаров</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $this->getViewData()['metrics']['total_products'] }}
                        </p>
                    </div>
                    <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-full">
                        <x-heroicon-o-cube class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Мало на складе</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ $this->getViewData()['metrics']['low_stock_items'] }}
                        </p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Последние заказы</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Сумма</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Статус</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Дата</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($this->getViewData()['recent_orders'] as $order)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $order['uuid'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($order['total_amount'], 2, ',', ' ') }} ₽
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'paid' => 'bg-green-100 text-green-800',
                                                    'processing' => 'bg-blue-100 text-blue-800',
                                                    'shipped' => 'bg-indigo-100 text-indigo-800',
                                                    'delivered' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $order['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $order['created_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Нет заказов</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Мало на складе</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Товар</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Доступно</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Всего</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Срок</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($this->getViewData()['low_stock_products'] as $item)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $item['product_name'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-red-600 dark:text-red-400">{{ $item['available'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item['total'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item['expires_at'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Все в норме</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Топ товаров</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Товар</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Заказы</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Цена</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($this->getViewData()['top_products'] as $product)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $product['name'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">{{ $product['orders_count'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ number_format($product['price'], 2, ',', ' ') }} ₽
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Revenue by Sub-Vertical -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Выручка по под-вертикалям</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Под-вертикаль</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Выручка</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Заказы</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($this->getViewData()['revenue_by_sub_vertical'] as $item)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $item['sub_vertical'] }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                            {{ number_format($item['revenue'], 2, ',', ' ') }} ₽
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $item['orders_count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Нет данных</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
