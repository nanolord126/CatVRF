<div class="aggregation-selector p-6 bg-white dark:bg-slate-900 rounded-lg shadow">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Выбор агрегации</h3>

    <!-- Переключатели агрегации -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach ($aggregations as $key => $agg)
            <button wire:click="updateAggregation('{{ $key }}')"
                class="p-4 rounded-lg border-2 transition {{ $aggregation === $key ? 'border-blue-500 bg-blue-50 dark:bg-blue-900 dark:border-blue-400' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                <div class="text-2xl mb-2">{{ $agg['icon'] }}</div>
                <div class="font-semibold text-gray-900 dark:text-white">{{ $agg['label'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $agg['description'] }}</div>
            </button>
        @endforeach
    </div>

    <!-- Метрики -->
    @if ($showLabels)
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Метрики для отображения</h4>
            <div class="flex flex-wrap gap-2">
                @foreach ($availableMetrics as $key => $label)
                    <button wire:click="toggleMetric('{{ $key }}')"
                        class="px-4 py-2 rounded-full transition {{ in_array($key, $selectedMetrics) ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
