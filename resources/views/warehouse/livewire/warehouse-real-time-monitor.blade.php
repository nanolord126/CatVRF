<div x-data="{
    autoRefresh: @js($autoRefresh),
    refreshInterval: @js($refreshInterval),
    timer: null
}" x-init="
    if (autoRefresh) {
        startAutoRefresh();
    }
">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Warehouse Real-Time Monitor</h1>
            <div class="flex gap-2">
                <button
                    wire:click="refreshData"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Refresh
                </button>
                <button
                    wire:click="toggleAutoRefresh"
                    class="px-4 py-2 {{ $autoRefresh ? 'bg-green-600' : 'bg-gray-600' }} text-white rounded-lg hover:opacity-90 transition"
                >
                    {{ $autoRefresh ? 'Auto: ON' : 'Auto: OFF' }}
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Warehouses</div>
                <div class="text-2xl font-bold">{{ $stats['total_warehouses'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Active Warehouses</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['active_warehouses'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Zones</div>
                <div class="text-2xl font-bold">{{ $stats['total_zones'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Avg Utilization</div>
                <div class="text-2xl font-bold {{ $stats['avg_utilization'] > 80 ? 'text-red-600' : 'text-blue-600' }}">
                    {{ number_format($stats['avg_utilization'], 1) }}%
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Low Stock Alerts</div>
                <div class="text-2xl font-bold text-orange-600">{{ $stats['low_stock_alerts'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Warehouses List -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold">Warehouses</h2>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    @forelse($warehouses as $warehouse)
                        <div
                            wire:click="selectWarehouse('{{ $warehouse['id'] }}')"
                            class="p-3 mb-2 rounded-lg cursor-pointer transition {{ $selectedWarehouseId === $warehouse['id'] ? 'bg-blue-100 border-blue-500' : 'bg-gray-50 hover:bg-gray-100' }}"
                        >
                            <div class="font-medium">{{ $warehouse['name'] }}</div>
                            <div class="text-sm text-gray-500">{{ $warehouse['type'] }}</div>
                            <div class="flex justify-between mt-2">
                                <span class="text-xs {{ $warehouse['is_active'] ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $warehouse['is_active'] ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ number_format($warehouse['utilization_percentage'], 1) }}% utilized
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-500 text-center py-4">No warehouses found</div>
                    @endforeach
                </div>
            </div>

            <!-- Zones (when warehouse selected) -->
            @if($selectedWarehouseId)
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Zones</h2>
                    </div>
                    <div class="p-4 max-h-96 overflow-y-auto">
                        @forelse($zones as $zone)
                            <div class="p-3 mb-2 rounded-lg bg-gray-50">
                                <div class="font-medium">{{ $zone['name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $zone['type'] }}</div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                    <div
                                        class="bg-blue-600 h-2 rounded-full"
                                        style="width: {{ $zone['utilization_percentage'] }}%"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $zone['current_stock'] }} / {{ $zone['capacity'] }} units
                                </div>
                            </div>
                        @empty
                            <div class="text-gray-500 text-center py-4">No zones found</div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Movements -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Recent Movements</h2>
                    </div>
                    <div class="p-4 max-h-96 overflow-y-auto">
                        @forelse($recentMovements as $movement)
                            <div class="p-3 mb-2 rounded-lg bg-gray-50">
                                <div class="flex justify-between">
                                    <span class="font-medium">{{ $movement['product_sku'] }}</span>
                                    <span class="text-sm {{ $movement['movement_type'] === 'receipt' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $movement['movement_type'] }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Qty: {{ $movement['quantity'] }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $movement['created_at'] }}
                                </div>
                            </div>
                        @empty
                            <div class="text-gray-500 text-center py-4">No recent movements</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function startAutoRefresh() {
            if (window.warehouseTimer) {
                clearInterval(window.warehouseTimer);
            }
            window.warehouseTimer = setInterval(() => {
                @this.refreshData();
            }, {{ $refreshInterval * 1000 }});
        }

        function stopAutoRefresh() {
            if (window.warehouseTimer) {
                clearInterval(window.warehouseTimer);
            }
        }

        // Cleanup on component destroy
        window.addEventListener('beforeunload', () => {
            stopAutoRefresh();
        });
    </script>
</div>
