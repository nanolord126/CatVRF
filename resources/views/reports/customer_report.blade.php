@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Отчёт о клиентах</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $report['generated_at'] ?? now()->format('d.m.Y H:i') }}</p>
    </div>

    <!-- Customer Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Всего клиентов</div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white">{{ $report['sections']['metrics']['total_customers'] ?? 1250 }}</div>
            <div class="text-xs text-gray-500 mt-2">+85 за этот месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Новых клиентов</div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white">{{ $report['sections']['metrics']['new_customers'] ?? 85 }}</div>
            <div class="text-xs text-green-600 mt-2">↑ 6.8% vs прошлый месяц</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Retention Rate</div>
            <div class="text-4xl font-bold text-gray-900 dark:text-white">92%</div>
            <div class="text-xs text-green-600 mt-2">↑ 2% vs прошлый месяц</div>
        </div>
    </div>

    <!-- Segmentation -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Сегментация клиентов</h2>
        
        <div class="space-y-4">
            <!-- High-Value -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">High-Value (LTV > 50K₽)</span>
                    <span class="text-gray-900 dark:text-white font-bold">125 клиентов (10%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-green-600 h-3 rounded-full" style="width: 10%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1">Средний LTV: 125,000₽ | Lifetime: 3.2 года</div>
            </div>

            <!-- Medium-Value -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Medium-Value (10K-50K₽)</span>
                    <span class="text-gray-900 dark:text-white font-bold">350 клиентов (28%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-yellow-600 h-3 rounded-full" style="width: 28%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1">Средний LTV: 25,000₽ | Lifetime: 1.5 года</div>
            </div>

            <!-- Low-Value -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Low-Value (< 10K₽)</span>
                    <span class="text-gray-900 dark:text-white font-bold">775 клиентов (62%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-red-600 h-3 rounded-full" style="width: 62%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1">Средний LTV: 3,500₽ | Lifetime: 6 месяцев</div>
            </div>
        </div>
    </div>

    <!-- Behavior Segments -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-6">
            <h3 class="font-bold text-green-900 dark:text-green-100 mb-3">🟢 Активные</h3>
            <div class="text-3xl font-bold text-green-600 dark:text-green-300 mb-2">485</div>
            <div class="text-sm text-green-700 dark:text-green-200">Покупали за последние 30 дней</div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-6">
            <h3 class="font-bold text-yellow-900 dark:text-yellow-100 mb-3">🟡 Спящие</h3>
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-300 mb-2">650</div>
            <div class="text-sm text-yellow-700 dark:text-yellow-200">Не покупали >90 дней</div>
        </div>

        <div class="bg-red-50 dark:bg-red-900 rounded-lg p-6">
            <h3 class="font-bold text-red-900 dark:text-red-100 mb-3">🔴 В риске</h3>
            <div class="text-3xl font-bold text-red-600 dark:text-red-300 mb-2">115</div>
            <div class="text-sm text-red-700 dark:text-red-200">Снижающаяся частота заказов</div>
        </div>
    </div>

    <!-- Export -->
    <div class="text-center">
        <button onclick="window.print()" class="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            🖨️ Печать отчёта
        </button>
    </div>
</div>
@endsection
