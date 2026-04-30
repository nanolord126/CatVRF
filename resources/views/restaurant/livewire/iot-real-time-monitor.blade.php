<div class="iot-real-time-monitor">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">IoT Real-Time Monitor</h2>
        
        <div class="flex items-center gap-4">
            <select 
                wire:model.live="selectedMetric"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="temperature">Temperature</option>
                <option value="weight">Weight</option>
                <option value="humidity">Humidity</option>
            </select>
            
            <button 
                wire:click="toggleAutoRefresh"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
            >
                {{ $autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF' }}
            </button>
            
            <button 
                wire:click="refresh"
                class="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-md transition-colors"
            >
                Refresh Now
            </button>
        </div>
    </div>

    @if(empty($devices))
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
            <p class="mt-2 text-gray-500">No online IoT devices found</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($devices as $device)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">{{ $device['name'] }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Online
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">{{ $device['type'] }}</p>
                        <p class="text-xs text-gray-400">{{ $device['identifier'] }}</p>
                    </div>
                    
                    <div class="p-4">
                        @if(isset($telemetryData[$device['id']]))
                            @php
                                $telemetry = $telemetryData[$device['id']];
                            @endphp
                            
                            @if($telemetry !== null)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 capitalize">{{ $selectedMetric }}</span>
                                    <div class="flex items-center gap-2">
                                        @if($telemetry['is_alert'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                Alert
                                            </span>
                                        @endif
                                        <span class="text-2xl font-bold {{ $telemetry['is_alert'] ? 'text-red-600' : 'text-gray-900' }}">
                                            {{ number_format($telemetry['value'], 2) }} {{ $telemetry['unit'] }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    Last update: {{ \Carbon\Carbon::parse($telemetry['recorded_at'])->diffForHumans() }}
                                </p>
                            @else
                                <p class="text-gray-400 text-sm">No data available</p>
                            @endif
                        @else
                            <p class="text-gray-400 text-sm">Loading...</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($autoRefresh)
        <script>
            setInterval(() => {
                @this.refresh();
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>
