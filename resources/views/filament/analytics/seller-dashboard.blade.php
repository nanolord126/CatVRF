<x-filament-panels::page>
    @if (empty($dashboardData))
        <x-filament-panels::empty-state
            heading="No Analytics Data Available"
            description="Start selling to see your analytics dashboard populate with data."
            icon="heroicon-o-chart-bar"
        />
    @else
        <div class="space-y-6">
            <!-- Period Selector -->
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900">
                    Seller Analytics Dashboard
                </h2>
                <div class="flex items-center space-x-2">
                    <x-filament::button
                        wire:click="changePeriod('7d')"
                        color="{{ $period === '7d' ? 'primary' : 'gray' }}"
                        size="sm"
                    >
                        7 Days
                    </x-filament::button>
                    <x-filament::button
                        wire:click="changePeriod('30d')"
                        color="{{ $period === '30d' ? 'primary' : 'gray' }}"
                        size="sm"
                    >
                        30 Days
                    </x-filament::button>
                    <x-filament::button
                        wire:click="changePeriod('90d')"
                        color="{{ $period === '90d' ? 'primary' : 'gray' }}"
                        size="sm"
                    >
                        90 Days
                    </x-filament::button>
                    <x-filament::button
                        wire:click="refresh"
                        icon="heroicon-o-arrow-path"
                        color="gray"
                        size="sm"
                    >
                        Refresh
                    </x-filament::button>
                </div>
            </div>

            <!-- KPI Cards Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach($dashboardData['kpi_cards'] ?? [] as $card)
                    <div class="rounded-lg border bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ $card['value'] }}
                                </p>
                            </div>
                            @if($card['icon'])
                                <div class="rounded-full bg-blue-50 p-2">
                                    <x-heroicon-o-{{ $card['icon'] }} class="h-5 w-5 text-blue-600" />
                                </div>
                            @endif
                        </div>
                        @if(isset($card['growth_rate']))
                            <div class="mt-2 flex items-center text-sm">
                                @if($card['trend'] === 'up')
                                    <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-green-500" />
                                @elseif($card['trend'] === 'down')
                                    <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-red-500" />
                                @endif
                                <span class="{{ $card['trend'] === 'up' ? 'text-green-600' : ($card['trend'] === 'down' ? 'text-red-600' : 'text-gray-600') }} ml-1">
                                    {{ $card['growth_rate'] > 0 ? '+' : '' }}{{ number_format($card['growth_rate'], 1) }}%
                                </span>
                                <span class="text-gray-500 ml-1">vs previous</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Charts are rendered via Filament widgets -->
                <div class="text-sm text-gray-500 italic">
                    Charts rendered by Filament widgets
                </div>
            </div>

            <!-- Top Products and Insights Section -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Top Products -->
                <div class="lg:col-span-2 rounded-lg border bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Products by Revenue</h3>
                    @if(!empty($dashboardData['top_products']['items'] ?? []))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Conversion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($dashboardData['top_products']['items'] as $item)
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $item['name'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500">
                                                ${{ number_format($item['value'], 2) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500">
                                                {{ $item['metadata']['orders'] ?? 0 }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500">
                                                {{ number_format($item['metadata']['conversion_rate'] ?? 0, 1) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex items-center justify-center h-48 text-gray-400">
                            <p>No product data available</p>
                        </div>
                    @endif
                </div>

                <!-- AI Insights -->
                <div class="rounded-lg border bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">AI Insights</h3>
                    @if(!empty($dashboardData['insights'] ?? []))
                        <div class="space-y-3">
                            @foreach($dashboardData['insights'] as $insight)
                                <div class="rounded-lg border p-3 {{ $insight['severity'] === 'critical' ? 'border-red-500 bg-red-50' : ($insight['severity'] === 'warning' ? 'border-yellow-500 bg-yellow-50' : ($insight['severity'] === 'opportunity' ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white')) }}">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold {{ $insight['severity'] === 'critical' ? 'text-red-800' : ($insight['severity'] === 'warning' ? 'text-yellow-800' : ($insight['severity'] === 'opportunity' ? 'text-green-800' : 'text-gray-800')) }}">
                                                {{ $insight['title'] }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-600">
                                                {{ $insight['message'] }}
                                            </p>
                                        </div>
                                        @if($insight['severity'] === 'critical')
                                            <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">!</span>
                                        @elseif($insight['severity'] === 'opportunity')
                                            <span class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">★</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center h-48 text-gray-400">
                            <p>No insights available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Period Info -->
            <div class="rounded-lg border bg-gray-50 p-4">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>
                        Period: {{ $dashboardData['period']['from'] }} to {{ $dashboardData['period']['to'] }}
                    </span>
                    <span>
                        Generated: {{ \Carbon\Carbon::parse($dashboardData['generated_at'])->format('Y-m-d H:i:s') }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
