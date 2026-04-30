<x-filament-panels::page>
    <x-slot name="heading">
        Big Data Health Monitor
    </x-slot>

    <div class="space-y-6">
        <!-- Health Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $health = $this->getHealthStatus();
                $isHealthy = $health['status'] === 'healthy';
            @endphp

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">ClickHouse Status</p>
                        <p class="text-2xl font-bold {{ $isHealthy ? 'text-green-600' : 'text-red-600' }}">
                            {{ $isHealthy ? 'Healthy' : 'Unhealthy' }}
                        </p>
                    </div>
                    <x-heroicon-o-server class="w-8 h-8 {{ $isHealthy ? 'text-green-500' : 'text-red-500' }}" />
                </div>
                @if(isset($health['version']))
                    <p class="text-xs text-gray-400 mt-2">v{{ $health['version'] }}</p>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Kafka Lag</p>
                        <p class="text-2xl font-bold text-blue-600">
                            {{ $this->getKafkaMetrics()['lag'] }}
                        </p>
                    </div>
                    <x-heroicon-o-arrow-path class="w-8 h-8 text-blue-500" />
                </div>
                <p class="text-xs text-gray-400 mt-2">messages</p>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Spark Jobs</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ $this->getSparkMetrics()['active_jobs'] }}
                        </p>
                    </div>
                    <x-heroicon-o-cpu-chip class="w-8 h-8 text-purple-500" />
                </div>
                <p class="text-xs text-gray-400 mt-2">active</p>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">24h Events</p>
                        <p class="text-2xl font-bold text-emerald-600">
                            {{ number_format(collect($this->getEventVolumeMetrics())->sum('event_count')) }}
                        </p>
                    </div>
                    <x-heroicon-o-chart-bar class="w-8 h-8 text-emerald-500" />
                </div>
                <p class="text-xs text-gray-400 mt-2">total</p>
            </div>
        </div>

        <!-- Table Statistics -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">ClickHouse Table Statistics</h3>
            </div>
            <div class="p-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rows</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->getTableStats() as $table => $stats)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $table }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($stats['rows']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($stats['status'] === 'ok')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            OK
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Error
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Event Volume Chart -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Event Volume (Last 24 Hours)</h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    @foreach($this->getEventVolumeMetrics() as $metric)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $metric['event_type'] }}</span>
                            <span class="text-sm font-medium">{{ number_format($metric['event_count']) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($metric['event_count'] / 10000) * 100) }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
