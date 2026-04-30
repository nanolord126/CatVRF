<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            @foreach($this->getStatisticsCards() as $card)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['title'] }}</p>
                            <p class="text-2xl font-bold text-{{ $card['color'] }}-600">{{ $card['value'] }}</p>
                        </div>
                        <x-heroicon-o-{{ $card['icon'] }} class="w-8 h-8 text-{{ $card['color'] }}-500" />
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-filament::select
                        label="Status"
                        wire:model.live="selectedStatus"
                        :options="[
                            'all' => 'All',
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'validating' => 'Validating',
                            'completed' => 'Completed',
                            'failed' => 'Failed',
                        ]"
                    />
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::select
                        label="Material Type"
                        wire:model.live="selectedMaterialType"
                        :options="[
                            'all' => 'All',
                            'fabric' => 'Fabric',
                            'leather' => 'Leather',
                            'denim' => 'Denim',
                            'knitwear' => 'Knitwear',
                            'silk' => 'Silk',
                            'synthetic' => 'Synthetic',
                            'wool' => 'Wool',
                            'linen' => 'Linen',
                            'velvet' => 'Velvet',
                            'suede' => 'Suede',
                            'canvas' => 'Canvas',
                            'technical' => 'Technical',
                            'mesh' => 'Mesh',
                            'rubber' => 'Rubber',
                            'plastic' => 'Plastic',
                        ]"
                    />
                </div>

                <x-filament::toggle
                    label="Auto Refresh"
                    wire:model.live="autoRefresh"
                />

                <x-filament::input
                    label="Refresh Interval (s)"
                    type="number"
                    wire:model.live="refreshInterval"
                    min="1"
                    max="60"
                />

                <x-filament::button
                    icon="heroicon-o-arrow-path"
                    wire:click="refresh"
                >
                    Refresh
                </x-filament::button>
            </div>
        </div>

        <!-- Stuck Generations Alert -->
        @if($this->getStuckGenerationsCount() > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                            {{ $this->getStuckGenerationsCount() }} generations have been stuck in processing for more than 30 minutes.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Recent Generations Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Generations</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Last 24 hours</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Material</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentGenerations as $generation)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $generation['name'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ ucfirst($generation['material_type']) }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $generation['status_color'] }}-100 text-{{ $generation['status_color'] }}-800 dark:bg-{{ $generation['status_color'] }}-900 dark:text-{{ $generation['status_color'] }}-200">
                                        <x-heroicon-o-{{ $generation['status_icon'] }} class="w-3 h-3 mr-1" />
                                        {{ $generation['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $generation['progress'] }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $generation['generation_time'] ? round($generation['generation_time']) . 's' : '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($generation['status'] === 'failed' && $generation['can_retry'])
                                        <x-filament::button
                                            size="sm"
                                            color="warning"
                                            wire:click="retryGeneration('{{ $generation['uuid'] }}')"
                                        >
                                            Retry
                                        </x-filament::button>
                                    @elseif(in_array($generation['status'], ['pending', 'processing']))
                                        <x-filament::button
                                            size="sm"
                                            color="danger"
                                            wire:click="cancelGeneration('{{ $generation['uuid'] }}')"
                                        >
                                            Cancel
                                        </x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No generations found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Failed Generations -->
        @if(!empty($failedGenerations))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Failed Generations</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Last 20 failures</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        wire:click="cleanupOldFailed"
                    >
                        Cleanup Old Failed
                    </x-filament::button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Material</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Retries</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($failedGenerations as $generation)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $generation['name'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ ucfirst($generation['material_type']) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-red-600 dark:text-red-400 max-w-xs truncate">
                                        {{ $generation['error_message'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $generation['retry_count'] }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($generation['can_retry'])
                                            <x-filament::button
                                                size="sm"
                                                color="warning"
                                                wire:click="retryGeneration('{{ $generation['uuid'] }}')"
                                            >
                                                Retry
                                            </x-filament::button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Storage Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Storage Usage</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Size</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $storageUsage['total_gb'] }} GB
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">In MB</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $storageUsage['total_mb'] }} MB
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">In Bytes</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($storageUsage['total_bytes']) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if($autoRefresh)
        <script>
            setInterval(() => {
                @this.call('refresh');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</x-filament-panels::page>
