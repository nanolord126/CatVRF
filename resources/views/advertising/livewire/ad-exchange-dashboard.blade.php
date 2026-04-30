<x-slot name="header">
    <h2 class="text-2xl font-bold text-gray-900">Ad Exchange Dashboard</h2>
    <div class="flex gap-2">
        <select wire:model.live="timeRange" class="rounded-md border-gray-300 shadow-sm">
            <option value="1h">Last Hour</option>
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
        </select>
        <button wire:click="toggleAutoRefresh" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            {{ $autoRefresh ? 'Auto-refresh: ON' : 'Auto-refresh: OFF' }}
        </button>
        <button wire:click="refreshMetrics" 
                class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
            Refresh
        </button>
    </div>
</x-slot>

<div class="space-y-6">
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Active Auctions</div>
            <div class="text-2xl font-bold text-gray-900">{{ $metrics['active_auctions'] ?? 0 }}</div>
            <div class="text-xs text-gray-400">Total: {{ $metrics['total_auctions'] ?? 0 }}</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Total Bids</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($metrics['total_bids'] ?? 0) }}</div>
            <div class="text-xs text-gray-400">Win Rate: {{ round(($metrics['win_rate'] ?? 0) * 100) }}%</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Revenue</div>
            <div class="text-2xl font-bold text-green-600">
                {{ number_format(($metrics['total_revenue'] ?? 0) / 100, 2) }} ₽
            </div>
            <div class="text-xs text-gray-400">CPM: {{ number_format(($metrics['cpm'] ?? 0) / 100, 2) }} ₽</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Impressions</div>
            <div class="text-2xl font-bold text-gray-900">
                {{ number_format($metrics['total_impressions'] ?? 0) }}
            </div>
            <div class="text-xs text-gray-400">CTR: {{ round(($metrics['ctr'] ?? 0) * 100, 2) }}%</div>
        </div>
    </div>

    <!-- Active Auctions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Active Auctions</h3>
        </div>
        <div class="p-6">
            @if(empty($activeAuctions))
                <div class="text-center text-gray-500 py-8">No active auctions</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bids</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Left</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($activeAuctions as $auction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $auction['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        {{ $auction['type'] === 'forward' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ ucfirst($auction['type']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">
                                    {{ number_format($auction['current_price'] / 100, 2) }} ₽
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $auction['bid_count'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ gmdate('H:i:s', $auction['time_remaining']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        {{ ucfirst($auction['status']) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Budget Pacing -->
    @if(!empty($pacingStatus))
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Budget Pacing Status</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($pacingStatus as $campaign)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $campaign['title'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ number_format($campaign['budget'] / 100, 2) }} ₽
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ number_format($campaign['spent'] / 100, 2) }} ₽
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="{{ $campaign['variance_percent'] > 10 ? 'text-red-600' : ($campaign['variance_percent'] < -10 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ round($campaign['variance_percent'], 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $campaign['is_on_track'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $campaign['is_on_track'] ? 'On Track' : 'Off Track' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Revenue Forecast -->
    @if(!empty($revenueForecast))
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">7-Day Revenue Forecast</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="text-sm text-gray-500">Total Forecasted Revenue</div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ number_format(($revenueForecast['total_revenue'] ?? 0) / 100, 2) }} ₽
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Average Daily Revenue</div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ number_format(($revenueForecast['average_daily'] ?? 0) / 100, 2) }} ₽
                    </div>
                </div>
            </div>
            @isset($revenueForecast['daily_breakdown'])
            <div class="h-48">
                <div class="flex items-end justify-between h-full gap-2">
                    @foreach($revenueForecast['daily_breakdown'] as $day)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-500 rounded-t" 
                             style="height: {{ ($day['estimated_revenue'] / ($revenueForecast['total_revenue'] ?? 1)) * 100 }}%">
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse($day['date'])->format('D') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endisset
        </div>
    </div>
    @endif

    <!-- Last Update -->
    <div class="text-sm text-gray-500 text-right">
        Last updated: {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->diffForHumans() : 'Never' }}
    </div>
</div>

@if($autoRefresh)
<script>
    setInterval(() => {
        @this.call('refreshMetrics');
    }, {{ $refreshInterval * 1000 }});
</script>
@endif
