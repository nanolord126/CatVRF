<?php declare(strict_types=1);

/**
 * FEATURE FLAGS (КАНОН 2026)
 *
 * Все флаги читаются из .env.
 * Управление через Filament Admin → Settings → Features.
 *
 * Правила использования:
 *   if (config('features.recommendations.enabled')) { ... }
 *   config('features.ml_fraud.enabled') ? FraudMLService::score(...) : FraudControlService::fallbackRules(...)
 */
return [

    // =========================================================================
    // Рекомендательная система (RecommendationService)
    // =========================================================================
    'recommendations' => [
        // Включить/выключить персонализацию (70% — можно отключить)
        'enabled' => (bool) env('RECOMMENDATIONS_ENABLED', true),

        // Доля персонализированных результатов (0.0 – 1.0)
        // При 0.0 — только популярные товары (fallback)
        'personalization_rate' => (float) env('RECOMMENDATIONS_PERSONALIZATION_RATE', 0.7),

        // Откат на популярные товары при нехватке данных
        'fallback_to_popular' => (bool) env('RECOMMENDATIONS_FALLBACK_TO_POPULAR', true),

        // TTL кэша в секундах
        'cache_ttl_dynamic' => (int) env('RECOMMENDATIONS_CACHE_TTL_DYNAMIC', 300),
        'cache_ttl_stable'  => (int) env('RECOMMENDATIONS_CACHE_TTL_STABLE', 3600),

        // A/B тест новой модели (feature flag для 10% трафика)
        'ab_test_model_v2'  => (bool) env('RECOMMENDATIONS_AB_TEST_V2', false),
    ],

    // =========================================================================
    // ML-фрод детекция (FraudMLService)
    // =========================================================================
    'ml_fraud' => [
        'enabled' => (bool) env('ML_FRAUD_ENABLED', true),

        // При false — откат на жёсткие правила FraudControlService::fallbackRules()
        'fallback_on_timeout' => (bool) env('ML_FRAUD_FALLBACK_ON_TIMEOUT', true),

        // Порог блокировки (0.0 – 1.0): score > threshold → block
        'block_threshold' => (float) env('ML_FRAUD_BLOCK_THRESHOLD', 0.85),

        // Порог ревью (требует ручной проверки)
        'review_threshold' => (float) env('ML_FRAUD_REVIEW_THRESHOLD', 0.65),

        // Логировать все операции с score > 0.5
        'log_suspicious'   => (bool) env('ML_FRAUD_LOG_SUSPICIOUS', true),
    ],

    // =========================================================================
    // Антифрод для вишлиста (WishlistService)
    // =========================================================================
    'wishlist_antifraud' => [
        'enabled'        => (bool) env('WISHLIST_ANTIFRAUD_ENABLED', true),

        // Максимум добавлений в вишлист за час (tenant-aware)
        'max_adds_per_hour' => (int) env('WISHLIST_MAX_ADDS_PER_HOUR', 100),

        // Требовать подтверждение при массовом удалении
        'require_confirm_bulk_delete' => (bool) env('WISHLIST_REQUIRE_CONFIRM_BULK_DELETE', true),
    ],

    // =========================================================================
    // Прогноз спроса (DemandForecastService)
    // =========================================================================
    'demand_forecast' => [
        'enabled'           => (bool) env('DEMAND_FORECAST_ENABLED', true),
        'cache_ttl_7days'   => (int) env('DEMAND_FORECAST_CACHE_TTL_7DAYS', 3600),
        'cache_ttl_30days'  => (int) env('DEMAND_FORECAST_CACHE_TTL_30DAYS', 86400),

        // Если MAPE > порога — откат на историческое среднее
        'fallback_mape_threshold' => (float) env('DEMAND_FORECAST_FALLBACK_MAPE', 0.25),
    ],

    // =========================================================================
    // Динамическое ценообразование (PriceSuggestionService)
    // =========================================================================
    'price_suggestion' => [
        'enabled'        => (bool) env('PRICE_SUGGESTION_ENABLED', true),

        // Автоматическое изменение цены без подтверждения бизнеса — ЗАПРЕЩЕНО
        'auto_apply'     => (bool) env('PRICE_SUGGESTION_AUTO_APPLY', false),

        // Максимальное отклонение от базовой цены (%)
        'max_increase_pct' => (int) env('PRICE_SUGGESTION_MAX_INCREASE_PCT', 50),
        'max_decrease_pct' => (int) env('PRICE_SUGGESTION_MAX_DECREASE_PCT', 30),
    ],

    // =========================================================================
    // AI квоты (AIQuotaService)
    // =========================================================================
    'ai_quotas' => [
        'enabled' => (bool) env('AI_QUOTAS_ENABLED', true),

        // Запросов в день по умолчанию (на tenant)
        'daily_requests_default' => (int) env('AI_QUOTAS_DAILY_DEFAULT', 1000),

        // Лимиты по тарифным планам
        'plans' => [
            'free'       => (int) env('AI_QUOTAS_PLAN_FREE', 100),
            'starter'    => (int) env('AI_QUOTAS_PLAN_STARTER', 1000),
            'business'   => (int) env('AI_QUOTAS_PLAN_BUSINESS', 10000),
            'enterprise' => (int) env('AI_QUOTAS_PLAN_ENTERPRISE', 100000),
        ],
    ],

    // =========================================================================
    // СБП (Система Быстрых Платежей)
    // =========================================================================
    'sbp_payments' => [
        'enabled'      => (bool) env('SBP_PAYMENTS_ENABLED', true),

        // Принудительный тип QR: QRDynamic (1 платёж) или QRStatic (многоразовый)
        'default_qr_type' => env('SBP_DEFAULT_QR_TYPE', 'QRDynamic'),

        // Двухстадийный платёж (hold + capture)
        'two_stage'    => (bool) env('SBP_TWO_STAGE', false),
    ],

    // =========================================================================
    // Email / Push уведомления
    // =========================================================================
    'notifications' => [
        'push_enabled'  => (bool) env('NOTIFICATIONS_PUSH_ENABLED', true),
        'email_enabled' => (bool) env('NOTIFICATIONS_EMAIL_ENABLED', true),
        'sms_enabled'   => (bool) env('NOTIFICATIONS_SMS_ENABLED', false),

        // Ежедневные отчёты бизнесу (8:00–9:00 по часовому поясу tenant)
        'daily_report_hour' => (int) env('NOTIFICATIONS_DAILY_REPORT_HOUR', 8),

        // Еженедельные отчёты (понедельник)
        'weekly_report_day' => (int) env('NOTIFICATIONS_WEEKLY_REPORT_DAY', 1), // 1 = Monday
    ],

    // =========================================================================
    // B2B функции
    // =========================================================================
    'b2b' => [
        'enabled'             => (bool) env('B2B_ENABLED', true),
        'supplier_catalog'    => (bool) env('B2B_SUPPLIER_CATALOG', true),
        'bulk_orders'         => (bool) env('B2B_BULK_ORDERS', true),
        'contract_management' => (bool) env('B2B_CONTRACT_MANAGEMENT', false),
    ],

    // =========================================================================
    // Экспериментальные функции (для разработчиков)
    // =========================================================================
    'experimental' => [
        // 3D-просмотр товаров (WebGL)
        '3d_viewer'           => (bool) env('FEATURE_3D_VIEWER', false),

        // AR-примерка (Beauty / Cosmetics)
        'ar_tryon'            => (bool) env('FEATURE_AR_TRYON', false),

        // Онлайн-видеоконсультации (WebRTC)
        'video_consultation'  => (bool) env('FEATURE_VIDEO_CONSULTATION', false),

        // Интеграция с умными замками (ShortTermRentals)
        'smart_locks'         => (bool) env('FEATURE_SMART_LOCKS', false),
    ],
];
