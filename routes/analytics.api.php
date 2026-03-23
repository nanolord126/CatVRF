<?php

declare(strict_types=1);

use App\Http\Controllers\Analytics\TimeSeriesHeatmapController;
use App\Http\Controllers\Analytics\ComparisonHeatmapController;
use App\Http\Controllers\Analytics\CustomMetricController;
use App\Http\Controllers\Analytics\ExportChartController;
use Illuminate\Support\Facades\Route;

/**
 * Analytics API Routes
 * 
 * Prefix: /api/analytics
 * Middleware: auth:sanctum (tenant-aware)
 * Rate Limiting: Built-in per endpoint
 */

Route::prefix('analytics')
    ->middleware('auth:sanctum')
    ->group(function () {
        
        /**
         * SECTION: Time-Series Heatmaps
         * ────────────────────────────────────────
         * Базовые временные ряды по гео и клик-данным
         */
        Route::prefix('heatmaps/timeseries')->group(function () {
            // GET /api/analytics/heatmaps/timeseries/geo
            // Параметры: vertical, from_date, to_date, aggregation, metric
            Route::get('geo', [TimeSeriesHeatmapController::class, 'geoTimeSeries'])
                ->name('analytics.heatmaps.timeseries.geo');
            
            // GET /api/analytics/heatmaps/timeseries/click
            // Параметры: vertical, page_url, from_date, to_date, aggregation
            Route::get('click', [TimeSeriesHeatmapController::class, 'clickTimeSeries'])
                ->name('analytics.heatmaps.timeseries.click');
        });

        /**
         * SECTION: Comparison Mode
         * ────────────────────────────────────────
         * Сравнение двух периодов (дельта-анализ, тренды)
         */
        Route::prefix('heatmaps/compare')->group(function () {
            // GET /api/analytics/heatmaps/compare/geo
            // Параметры: vertical, period1_from, period1_to, period2_from, period2_to, metric
            Route::get('geo', [ComparisonHeatmapController::class, 'compareGeo'])
                ->name('analytics.heatmaps.compare.geo');
            
            // GET /api/analytics/heatmaps/compare/click
            // Параметры: vertical, page_url, period1_from, period1_to, period2_from, period2_to
            Route::get('click', [ComparisonHeatmapController::class, 'compareClick'])
                ->name('analytics.heatmaps.compare.click');
        });

        /**
         * SECTION: Custom Metrics
         * ────────────────────────────────────────
         * Производные метрики: интенсивность, вовлечённость, рост, концентрация и т.д.
         * 
         * Geo metrics:
         * - event_intensity: События/день/геохэш
         * - engagement_score: Комбинированная оценка вовлечённости
         * - growth_rate: Темп роста
         * - hotspot_concentration: Концентрация горячих точек
         * - user_retention: Удержание пользователей
         * 
         * Click metrics:
         * - click_density: Плотность кликов
         * - interaction_score: Оценка взаимодействия
         * - user_engagement: Вовлечённость пользователя
         * - click_conversion: Конверсия по кликам
         */
        Route::prefix('heatmaps/custom')->group(function () {
            // GET /api/analytics/heatmaps/custom/geo
            // Параметры: vertical, metric, from_date, to_date, aggregation
            Route::get('geo', [CustomMetricController::class, 'customGeo'])
                ->name('analytics.heatmaps.custom.geo');
            
            // GET /api/analytics/heatmaps/custom/click
            // Параметры: vertical, metric, page_url, from_date, to_date, aggregation
            Route::get('click', [CustomMetricController::class, 'customClick'])
                ->name('analytics.heatmaps.custom.click');
        });

        /**
         * SECTION: Reports (Future)
         * ────────────────────────────────────────
         * Генерация отчётов, экспорт данных
         */
        Route::prefix('export')->group(function () {
            // POST /api/analytics/export/png
            // Body: { chart_image: "data:image/png;base64,..." }
            Route::post('png', [ExportChartController::class, 'exportPng'])
                ->name('analytics.export.png');
            
            // POST /api/analytics/export/pdf
            // Body: { chart_data: {...}, title: "...", description: "..." }
            Route::post('pdf', [ExportChartController::class, 'exportPdf'])
                ->name('analytics.export.pdf');
            
            // POST /api/analytics/export/quick
            // Быстрый экспорт с сохранением в storage
            Route::post('quick', [ExportChartController::class, 'quickExport'])
                ->name('analytics.export.quick');
        });

        Route::prefix('reports')->group(function () {
            // Placeholder для будущих эндпоинтов
            // Route::get('generate', [ReportController::class, 'generate']);
            // Route::post('export', [ReportController::class, 'export']);
        });
    });
