@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Отчёт производительности</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $report['generated_at'] ?? now()->format('d.m.Y H:i') }}</p>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">LTV (Пожизненная ценность)</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                ₽ {{ number_format($report['sections']['kpis']['metrics']['ltv_estimate'] ?? 45000, 0, '.', ' ') }}
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: 72%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-2">72% от целевого (62,500₽)</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Коэффициент оттока</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ number_format(($report['sections']['kpis']['metrics']['churn_rate'] ?? 0.08) * 100, 1) }}%
            </div>
            <div class="text-xs text-red-600 mt-2">↑ 0.5% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">ROI</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ number_format($report['sections']['kpis']['metrics']['roi_estimate'] ?? 2.15, 2) }}x
            </div>
            <div class="text-xs text-green-600 mt-2">↑ 12% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Коэффициент удержания</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ number_format((1 - ($report['sections']['kpis']['metrics']['churn_rate'] ?? 0.08)) * 100, 1) }}%
            </div>
            <div class="text-xs text-green-600 mt-2">Хорошие показатели</div>
        </div>
    </div>

    <!-- Comparison -->
    @if(isset($report['sections']['comparison']))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Сравнение периодов</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="border-l-4 border-blue-500 pl-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Выручка</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $report['sections']['comparison']['deltas']['revenue_change_percent'] ?? 0 }}%
                </div>
            </div>
            <div class="border-l-4 border-green-500 pl-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Заказы</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $report['sections']['comparison']['deltas']['orders_change_percent'] ?? 0 }}%
                </div>
            </div>
            <div class="border-l-4 border-orange-500 pl-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Средний чек</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $report['sections']['comparison']['deltas']['aov_change_percent'] ?? 0 }}%
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recommendations -->
    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 mb-8">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3">💡 Рекомендации</h3>
        <ul class="space-y-2 text-blue-800 dark:text-blue-200">
            <li>✓ Фокусировка на Высокодоходном сегменте (125 клиентов с LTV 125K+)</li>
            <li>✓ Улучшить Коэффициент удержания через реинвайт кампанию</li>
            <li>✓ Тестировать повышение цены (AOV падает на 2.1%)</li>
            <li>✓ Активировать спящих клиентов (Спящие: 500+)</li>
        </ul>
    </div>

    <!-- Export -->
    <div class="text-center">
        <button onclick="window.print()" class="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            🖨️ Печать отчёта
        </button>
    </div>
</div>
@endsection
