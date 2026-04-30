<x-filament-panels::page>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">ML Model Drift Dashboard</h2>
                <p class="text-sm text-gray-500 mt-1">Real-time monitoring of ML model drift across all models and verticals</p>
            </div>
            <div class="flex gap-2">
                <x-filament::button 
                    color="warning" 
                    wire:click="$dispatch('refresh')"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                    Refresh
                </x-filament::button>
                <x-filament::button 
                    color="primary"
                    href="{{ DriftMonitoringResource::getUrl('index') }}"
                >
                    <x-heroicon-o-table-cells class="w-4 h-4 mr-2" />
                    List View
                </x-filament::button>
            </div>
        </div>
    </x-slot>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Models</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $viewData['overall_stats']['total_models'] }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <x-heroicon-o-cpu-chip class="w-6 h-6 text-blue-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Critical Drift</p>
                    <p class="text-3xl font-bold text-red-600">{{ $viewData['overall_stats']['critical_drift'] }}</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Warning Drift</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $viewData['overall_stats']['warning_drift'] }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <x-heroicon-o-exclamation-circle class="w-6 h-6 text-yellow-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Normal</p>
                    <p class="text-3xl font-bold text-green-600">{{ $viewData['overall_stats']['normal'] }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </div>
    </div>

    <!-- Drift by Model -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Drift by Model</h3>
            <div class="space-y-4">
                @foreach($viewData['drift_by_model'] as $model)
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium">{{ $model['model'] }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($model['drift_score'], 3) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full {{ $model['status'] === 'critical' ? 'bg-red-600' : ($model['status'] === 'warning' ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                     style="width: {{ min($model['drift_score'] * 100, 100) }}%"></div>
                            </div>
                        </div>
                        <span class="ml-4 px-2 py-1 text-xs font-medium rounded {{ $model['status'] === 'critical' ? 'bg-red-100 text-red-800' : ($model['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ ucfirst($model['status']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Drift by Vertical -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Drift by Vertical</h3>
            <div class="space-y-4">
                @foreach($viewData['drift_by_vertical'] as $vertical)
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium">{{ $vertical['vertical'] }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($vertical['drift_score'], 3) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full {{ $vertical['status'] === 'critical' ? 'bg-red-600' : ($vertical['status'] === 'warning' ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                     style="width: {{ min($vertical['drift_score'] * 100, 100) }}%"></div>
                            </div>
                        </div>
                        <span class="ml-4 px-2 py-1 text-xs font-medium rounded {{ $vertical['status'] === 'critical' ? 'bg-red-100 text-red-800' : ($vertical['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ ucfirst($vertical['status']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Alerts & Top Drifting Features -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Recent Alerts</h3>
            <div class="space-y-4">
                @foreach($viewData['recent_alerts'] as $alert)
                    <div class="border-l-4 {{ $alert['severity'] === 'critical' ? 'border-red-500' : 'border-yellow-500' }} pl-4 py-2">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $alert['model'] }} ({{ $alert['vertical'] }})</span>
                            <span class="text-xs text-gray-500">{{ $alert['timestamp'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $alert['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Top Drifting Features</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($viewData['top_drifting_features'] as $feature)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">{{ $feature['feature'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $feature['model'] }}</td>
                                <td class="px-4 py-3 text-sm {{ $feature['psi'] > 0.25 ? 'text-red-600 font-medium' : '' }}">
                                    {{ number_format($feature['psi'], 3) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <div class="text-sm text-gray-500">
            Last analysis: {{ $viewData['overall_stats']['last_analysis'] }} | 
            Data refreshed: {{ now()->toDateTimeString() }}
        </div>
    </x-slot>
</x-filament-panels::page>
