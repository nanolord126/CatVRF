@extends('layouts.app')

@section('title', 'Analytics - Heatmaps')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb Navigation -->
        @livewire('analytics.components.breadcrumb-component', ['vertical' => 'beauty', 'heatmapType' => 'geo'])

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Аналитика - Тепловые карты</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Анализируйте активность пользователей в реальном времени с автоматическими обновлениями</p>
        </div>

        <!-- Filter Persistence Helper -->
        @livewire('analytics.components.filter-persistence-component')

        <!-- Фильтры -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Вертикаль -->
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Вертикаль</label>
                <select id="verticalFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                    <option value="beauty">Красота</option>
                    <option value="auto">Авто</option>
                    <option value="food">Еда</option>
                    <option value="hotels">Гостиницы</option>
                    <option value="realestate">Недвижимость</option>
                </select>
            </div>

            <!-- Тип heatmap -->
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Тип анализа</label>
                <div class="flex gap-2">
                    <button id="geoTypeBtn" data-type="geo" class="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        📍 Геолокация
                    </button>
                    <button id="clickTypeBtn" data-type="click" class="flex-1 px-3 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        🖱️ Клики
                    </button>
                </div>
            </div>

            <!-- Диапазон дат -->
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Период</label>
                <div class="flex gap-2">
                    <input type="date" id="fromDateFilter" value="{{ now()->subDays(30)->format('Y-m-d') }}"
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <input type="date" id="toDateFilter" value="{{ now()->format('Y-m-d') }}"
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </div>
            </div>
        </div>

        <!-- Главное содержимое -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Левая колонка - Селекторы -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Агрегация -->
                <livewire:analytics.aggregation-selector-component />

                <!-- Сравнение -->
                <livewire:analytics.comparison-mode-picker-component />

                <!-- Кастомные метрики -->
                <livewire:analytics.custom-metric-selector-component />
            </div>

            <!-- Правая колонка - График -->
            <div class="lg:col-span-2">
                <livewire:analytics.time-series-chart-component
                    :vertical="request('vertical', 'beauty')"
                    :heatmap_type="request('heatmap_type', 'geo')"
                />
            </div>
        </div>

        <!-- Дополнительная информация -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Справка</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-gray-600 dark:text-gray-400">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">Геолокация</p>
                    <p>Анализируйте активность пользователей по географическому положению. Идеально для понимания территориального спроса.</p>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">Клики</p>
                    <p>Отслеживайте взаимодействие пользователей с интерфейсом. Определяйте горячие зоны и улучшайте UX.</p>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">Сравнение</p>
                    <p>Сравнивайте две временные периоды для анализа изменений. Используйте быстрые настройки для популярных сравнений.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Переключение типа анализа
    document.getElementById('geoTypeBtn').addEventListener('click', function() {
        document.getElementById('geoTypeBtn').classList.replace('bg-gray-300', 'bg-blue-500');
        document.getElementById('geoTypeBtn').classList.add('text-white');
        document.getElementById('clickTypeBtn').classList.replace('bg-blue-500', 'bg-gray-300');
        document.getElementById('clickTypeBtn').classList.remove('text-white');
        // Обновить компонент в Livewire
    });

    document.getElementById('clickTypeBtn').addEventListener('click', function() {
        document.getElementById('clickTypeBtn').classList.replace('bg-gray-300', 'bg-blue-500');
        document.getElementById('clickTypeBtn').classList.add('text-white');
        document.getElementById('geoTypeBtn').classList.replace('bg-blue-500', 'bg-gray-300');
        document.getElementById('geoTypeBtn').classList.remove('text-white');
        // Обновить компонент в Livewire
    });
</script>
@endsection
