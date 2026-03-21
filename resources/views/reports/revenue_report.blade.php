@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Отчёт о выручке</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $report['generated_at'] ?? now()->format('d.m.Y H:i') }}</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Общая выручка</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                ₽ {{ number_format($report['sections']['kpis']['metrics']['total_revenue_30d'] ?? 250000, 0, '.', ' ') }}
            </div>
            <div class="text-xs text-green-600 mt-2">↑ 12.5% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Заказов</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $report['sections']['kpis']['metrics']['total_orders_30d'] ?? 125 }}
            </div>
            <div class="text-xs text-green-600 mt-2">↑ 8.3% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Средний чек</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                ₽ {{ number_format($report['sections']['kpis']['metrics']['avg_order_value'] ?? 2000, 0, '.', ' ') }}
            </div>
            <div class="text-xs text-red-600 mt-2">↓ 2.1% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Конверсия</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format(($report['sections']['kpis']['metrics']['conversion_rate'] ?? 0.045) * 100, 2) }}%
            </div>
            <div class="text-xs text-green-600 mt-2">↑ 0.8% vs прошлый месяц</div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Разбор по категориям</h2>
        </div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-3 px-6 text-gray-900 dark:text-white font-bold">Категория</th>
                    <th class="text-right py-3 px-6 text-gray-900 dark:text-white font-bold">Выручка</th>
                    <th class="text-right py-3 px-6 text-gray-900 dark:text-white font-bold">Заказов</th>
                    <th class="text-right py-3 px-6 text-gray-900 dark:text-white font-bold">Доля</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories ?? [] as $category)
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="py-3 px-6 text-gray-900 dark:text-white">{{ $category['name'] ?? 'Category' }}</td>
                    <td class="py-3 px-6 text-right text-gray-700 dark:text-gray-300">₽ {{ number_format($category['revenue'] ?? 0, 0, '.', ' ') }}</td>
                    <td class="py-3 px-6 text-right text-gray-700 dark:text-gray-300">{{ $category['orders'] ?? 0 }}</td>
                    <td class="py-3 px-6 text-right text-gray-700 dark:text-gray-300">{{ $category['share'] ?? '0' }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 px-6 text-center text-gray-500">Нет данных</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Export Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Экспорт отчёта</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('exports.download', ['format' => 'csv']) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                📄 Скачать CSV
            </a>
            <a href="{{ route('exports.download', ['format' => 'excel']) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                📊 Скачать Excel
            </a>
            <a href="{{ route('exports.download', ['format' => 'pdf']) }}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                📃 Скачать PDF
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                🖨️ Печать
            </button>
        </div>
    </div>
</div>
@endsection
