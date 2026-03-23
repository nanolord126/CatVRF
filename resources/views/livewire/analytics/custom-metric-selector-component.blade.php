<div class="custom-metric-selector p-6 bg-white dark:bg-slate-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Кастомные метрики</h3>
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" wire:change="toggleEnabled()" {{ $isEnabled ? 'checked' : '' }}
                class="mr-2 w-4 h-4 rounded">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $isEnabled ? 'Включено' : 'Отключено' }}
            </span>
        </label>
    </div>

    @if ($isEnabled)
        <!-- Geo метрики -->
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Геометрии</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($geoMetrics as $key => $metric)
                    <button wire:click="selectMetric('{{ $key }}')"
                        class="p-3 rounded-lg border-2 transition text-left {{ $selectedMetric === $key ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <div class="text-lg mb-1">{{ $metric['icon'] }}</div>
                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $metric['name'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $metric['description'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Click метрики -->
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Клики</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($clickMetrics as $key => $metric)
                    <button wire:click="selectMetric('{{ $key }}')"
                        class="p-3 rounded-lg border-2 transition text-left {{ $selectedMetric === $key ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <div class="text-lg mb-1">{{ $metric['icon'] }}</div>
                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $metric['name'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $metric['description'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
