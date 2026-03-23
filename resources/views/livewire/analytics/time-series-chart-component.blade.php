<div wire:poll.30000ms="pollChartData" wire:ignore class="analytics-chart-container bg-white dark:bg-slate-900 rounded-lg shadow-lg p-6">
    <!-- Header с действиями -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                @if ($isCustomMetric)
                    Кастомная метрика: {{ ucfirst(str_replace('_', ' ', $customMetric)) }}
                @elseif ($isComparison)
                    Сравнение периодов
                @else
                    Временной ряд
                @endif
                <span class="text-sm text-green-500 ml-2">🔄 Real-time (30s)</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Correlation ID: <code class="text-xs">{{ substr($correlationId, 0, 8) }}...</code>
            </p>
        </div>

        <!-- Кнопки действий -->
        <div class="flex gap-2">
            @if ($enableExport)
                <button wire:click="exportPng" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                    📥 PNG
                </button>
                <button wire:click="exportPdf" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                    📄 PDF
                </button>
            @endif
        </div>
    </div>

    <!-- Ошибка если есть -->
    @if ($errorMessage)
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Loading -->
    @if ($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <span class="ml-4 text-gray-700 dark:text-gray-300">Загрузка данных...</span>
        </div>
    @else
        <!-- Контролы -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Тип графика -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип графика</label>
                <div class="flex gap-2">
                    @foreach ($chartTypes as $type)
                        <button wire:click="updateChartType('{{ $type }}')"
                            class="px-3 py-2 rounded-lg transition {{ $chartType === $type ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                            {{ ucfirst($type) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Агрегация -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Агрегация</label>
                <div class="flex gap-2">
                    @foreach ($availableAggregations as $agg)
                        <button wire:click="updateAggregation('{{ $agg }}')"
                            class="px-3 py-2 rounded-lg transition text-sm {{ $aggregation === $agg ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                            {{ ucfirst($agg) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Метрика -->
            @if (!$isCustomMetric)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Метрика</label>
                    <select wire:change="updateMetric($event.target.value)" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                        @foreach ($availableMetrics as $m)
                            <option value="{{ $m }}" {{ $metric === $m ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $m)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Режимы -->
            <div class="flex gap-2 items-end">
                <button wire:click="toggleComparisonMode()"
                    class="px-3 py-2 rounded-lg transition {{ $isComparison ? 'bg-purple-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                    ⚖️ Сравнение
                </button>
                
                @if ($heatmapType === 'geo')
                    <button wire:click="toggleCustomMetric()"
                        class="px-3 py-2 rounded-lg transition {{ $isCustomMetric ? 'bg-purple-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        📊 Метрики
                    </button>
                @endif
            </div>
        </div>

        <!-- Дополнительные параметры для сравнения -->
        @if ($isComparison)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 1 от</label>
                    <input type="date" wire:model="period1From" wire:change="loadChartData()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 1 до</label>
                    <input type="date" wire:model="period1To" wire:change="loadChartData()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 2 от</label>
                    <input type="date" wire:model="period2From" wire:change="loadChartData()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 2 до</label>
                    <input type="date" wire:model="period2To" wire:change="loadChartData()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </div>
            </div>
        @endif

        <!-- Селектор кастомных метрик -->
        @if ($isCustomMetric && $heatmapType === 'geo')
            <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Выберите метрику</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($availableCustomMetrics as $key => $label)
                        <button wire:click="toggleCustomMetric('{{ $key }}')"
                            class="px-3 py-2 rounded-lg transition text-sm {{ $customMetric === $key ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Основной график -->
        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 mb-6">
            <canvas id="timeSeriesChart" width="400" height="200"></canvas>
        </div>

        <!-- Metadata информация -->
        @if (!empty($chartData['metadata']))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Всего событий</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ number_format($chartData['metadata']['total'] ?? 0, 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Уникальных пользователей</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ number_format($chartData['metadata']['users'] ?? 0, 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Период</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $fromDate }} — {{ $toDate }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Сгенерировано</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $chartData['metadata']['generated_at'] ?? now()->format('H:i:s') }}
                    </p>
                </div>
            </div>
        @endif
    @endif

    <!-- JavaScript для Chart.js -->
    <script type="module">
        import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/+esm';

        // Когда компонент обновляется, перестраиваем график
        document.addEventListener('livewire:updated', () => {
            const config = @json($chartConfig);
            if (config && Object.keys(config).length > 0) {
                const ctx = document.getElementById('timeSeriesChart');
                if (window.chartInstance) {
                    window.chartInstance.destroy();
                }
                window.chartInstance = new Chart(ctx, config);
            }
        });

        // Инициализировать график при загрузке
        window.addEventListener('load', () => {
            const config = @json($chartConfig);
            if (config && Object.keys(config).length > 0) {
                const ctx = document.getElementById('timeSeriesChart');
                window.chartInstance = new Chart(ctx, config);
            }
        });

        // Экспорт PNG - скачать с браузера
        Livewire.on('export-chart-png', () => {
            if (window.chartInstance) {
                const image = window.chartInstance.toBase64Image();
                const link = document.createElement('a');
                link.href = image;
                link.download = 'chart-' + new Date().getTime() + '.png';
                link.click();
                
                // Логировать на сервер
                fetch('/api/analytics/export/png', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Correlation-ID': @json($correlationId),
                    },
                    body: JSON.stringify({
                        chart_image: image,
                        filename: link.download,
                    }),
                }).catch(err => console.log('PNG export logged'));
            }
        });

        // Экспорт PDF через сервер
        Livewire.on('export-chart-pdf', (data) => {
            const chartData = data.chartData || @json($chartData);
            const chartImage = window.chartInstance ? window.chartInstance.toBase64Image() : null;
            
            fetch('/api/analytics/export/pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Correlation-ID': @json($correlationId),
                },
                body: JSON.stringify({
                    chart_data: chartData,
                    chart_image: chartImage,
                    title: 'Analytics Report - ' + new Date().toLocaleDateString('ru-RU'),
                    description: 'Аналитический отчёт по тепловым картам',
                    metadata: {
                        total: chartData.metadata?.total ?? 0,
                        users: chartData.metadata?.users ?? 0,
                        period: @json($fromDate) + ' — ' + @json($toDate),
                    },
                }),
            }).then(res => res.blob())
              .then(blob => {
                  const link = document.createElement('a');
                  link.href = URL.createObjectURL(blob);
                  link.download = 'report-' + new Date().getTime() + '.pdf';
                  link.click();
              })
              .catch(err => console.error('PDF export error:', err));
        });

        // Слушать WebSocket обновления
        @if (config('broadcasting.default') === 'reverb')
            const tenantId = @json(auth()?->user()?->current_tenant_id ?? filament()?->getTenant()?->id ?? 1);
            
            // Слушать синхронизацию гео-событий
            Echo.private('analytics.tenant.' + tenantId)
                .listen('GeoEventsSyncedToClickHouse', (e) => {
                    console.log('🔄 Geo events synced to ClickHouse', {
                        events: e.metadata?.events_synced,
                        duration: e.metadata?.duration,
                        correlation_id: e.correlation_id,
                    });
                    
                    // Перезагрузить данные с задержкой (ClickHouse пропагация ~2-5 сек)
                    setTimeout(() => {
                        Livewire.dispatch('reload-chart-data');
                    }, 2500);
                    
                    // Показать уведомление
                    if (window.notificationQueue) {
                        window.notificationQueue.add({
                            type: 'info',
                            title: 'Данные обновлены',
                            message: `Синхронизировано ${e.metadata?.events_synced ?? 0} событий`,
                            icon: '✨',
                        });
                    }
                });
            
            // Слушать синхронизацию клик-событий
            Echo.private('analytics.tenant.' + tenantId)
                .listen('ClickEventsSyncedToClickHouse', (e) => {
                    console.log('🔄 Click events synced to ClickHouse', {
                        events: e.metadata?.events_synced,
                        duration: e.metadata?.duration,
                        correlation_id: e.correlation_id,
                    });
                    
                    // Перезагрузить данные с задержкой
                    setTimeout(() => {
                        Livewire.dispatch('reload-chart-data');
                    }, 2500);
                    
                    // Показать уведомление
                    if (window.notificationQueue) {
                        window.notificationQueue.add({
                            type: 'info',
                            title: 'Данные обновлены',
                            message: `Синхронизировано ${e.metadata?.events_synced ?? 0} кликов`,
                            icon: '✨',
                        });
                    }
                });
        @endif
    </script>
</div>
