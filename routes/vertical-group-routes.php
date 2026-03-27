<?php declare(strict_types=1);

/**
 * Vertical Route Groups Configuration — Production 2026 CANON
 *
 * ПРАВИЛА ROUTING ARCHITECTURE:
 *
 * 1. ❌ НИКОГДА не используй $this->middleware() в конструкторе контроллера!
 * 2. ✅ ВСЕГДА используй middleware в route groups (Route::middleware([...]))
 * 3. ✅ Middleware определение ОДИН РАЗ в Route::group()
 * 4. ✅ Контроллер ТОЛЬКО получает зависимости через constructor injection
 *
 * MIDDLEWARE ORDERING (обязательный порядок):
 * - correlation-id          (генерация ID)
 * - enrich-context          (IP, user_agent)
 * - auth:sanctum            (проверка токена)
 * - tenant                  (tenant scoping)
 * - b2c-b2b                 (B2C/B2B определение)
 * - rate-limit              (глобальный)
 * - fraud-check             (критичные операции)
 * - age-verify              (для вертикалей с возр. ограничениями)
 *
 * MIDDLEWARE GROUPS (примеры):
 */

// ===== BASE MIDDLEWARE CONFIGURATION =====
const BASE_MIDDLEWARE = [
    'correlation-id',      // Инжектировать X-Correlation-ID в все запросы
    'enrich-context',      // Добавить IP, user_agent, timing metadata
];

const AUTH_MIDDLEWARE = [
    'auth:sanctum',        // API токен валидация
    'tenant',              // Tenant scoping & validation
];

const CRITICAL_OPERATION_MIDDLEWARE = [
    'b2c-b2b',             // B2C/B2B mode determination
    'rate-limit',          // Tenant-aware throttling
    'fraud-check',         // Fraud ML detection
];

const AGE_RESTRICTED_MIDDLEWARE = [
    'age-verify',          // Age verification (Pharmacy, Vapes, Alcohol)
];

// ===== VERTICAL-SPECIFIC MIDDLEWARE GROUPS =====

/**
 * BEAUTY & WELLNESS
 * Middleware для вертикали красоты/здоровья:
 * - Нет возрастных ограничений (отдельно - консультации для взрослых)
 * - Standard fraud check
 * - Rate limit: 100 req/min
 */
const BEAUTY_MIDDLEWARE = [
    ...BASE_MIDDLEWARE,
    ...AUTH_MIDDLEWARE,
    ...CRITICAL_OPERATION_MIDDLEWARE,
    'rate-limit-beauty:100,1',  // Beauty-specific rate limit
];

/**
 * FOOD & RESTAURANTS
 * Middleware для вертикали еды:
 * - Нет возрастных ограничений
 * - Standard fraud check
 * - Rate limit: 150 req/min (more traffic)
 */
const FOOD_MIDDLEWARE = [
    ...BASE_MIDDLEWARE,
    ...AUTH_MIDDLEWARE,
    ...CRITICAL_OPERATION_MIDDLEWARE,
    'rate-limit-food:150,1',
];

/**
 * PHARMACY & MEDICAL
 * Middleware для аптек/медицины:
 * - AGE VERIFICATION (18+ для некоторых товаров)
 * - Strict fraud check
 * - Medical audit logging
 * - Rate limit: 80 req/min
 */
const PHARMACY_MIDDLEWARE = [
    ...BASE_MIDDLEWARE,
    ...AUTH_MIDDLEWARE,
    ...CRITICAL_OPERATION_MIDDLEWARE,
    ...AGE_RESTRICTED_MIDDLEWARE,
    'medical-audit',       // Medical-specific audit logging
    'rate-limit-pharmacy:80,1',
];

/**
 * ALCOHOL & VAPES
 * Middleware для алкоголя и вейпов:
 * - AGE VERIFICATION (18+)
 * - Strict fraud check
 * - Rate limit: 50 req/min (strict)
 */
const ALCOHOL_VAPES_MIDDLEWARE = [
    ...BASE_MIDDLEWARE,
    ...AUTH_MIDDLEWARE,
    ...CRITICAL_OPERATION_MIDDLEWARE,
    ...AGE_RESTRICTED_MIDDLEWARE,
    'rate-limit-alcohol:50,1',
];

/**
 * PAYMENTS & WALLET
 * Middleware для платежей:
 * - Duplicate payment check (idempotency)
 * - Strict fraud detection
 * - Rate limit: 10 req/min (ОЧЕНЬ СТРОГО)
 */
const PAYMENT_MIDDLEWARE = [
    ...BASE_MIDDLEWARE,
    ...AUTH_MIDDLEWARE,
    'b2c-b2b',
    'idempotency-check',   // Проверка повторных платежей
    'fraud-check',         // STRICT fraud detection
    'rate-limit:10,1',     // 10 req/min MAX
];

/**
 * WEBHOOK ROUTES (No Auth)
 * Middleware для вебхуков:
 * - IP Whitelist (только от платежных систем)
 * - Signature verification (HMAC-SHA256)
 * - NO rate-limit (разрешить сразу)
 */
const WEBHOOK_MIDDLEWARE = [
    'correlation-id',
    'webhook-signature',   // HMAC-SHA256 verification
    'ip-whitelist',        // IP whitelist for gateways
];

// ===== USAGE EXAMPLES IN ROUTES =====

/*
 * ✅ ПРАВИЛЬНЫЙ СПОСОБ 1: Группировка в api-v1.php
 *
 * Route::prefix('beauty')
 *     ->middleware(BEAUTY_MIDDLEWARE)
 *     ->group(function () {
 *         Route::apiResource('salons', BeautySalonController::class);
 *         Route::apiResource('appointments', AppointmentController::class);
 *     });
 */

/*
 * ✅ ПРАВИЛЬНЫЙ СПОСОБ 2: Платежи с дополнительной защитой
 *
 * Route::prefix('payments')
 *     ->middleware(PAYMENT_MIDDLEWARE)
 *     ->group(function () {
 *         Route::post('/init', [PaymentController::class, 'init']);
 *         Route::post('/{payment}/capture', [PaymentController::class, 'capture']);
 *     });
 */

/*
 * ✅ ПРАВИЛЬНЫЙ СПОСОБ 3: Вебхуки (без аутентификации)
 *
 * Route::prefix('webhooks')
 *     ->middleware(WEBHOOK_MIDDLEWARE)
 *     ->group(function () {
 *         Route::post('/tinkoff', [WebhookController::class, 'tinkoff']);
 *         Route::post('/tochka', [WebhookController::class, 'tochka']);
 *     });
 */

/*
 * ❌ НЕПРАВИЛЬНЫЙ СПОСОБ: $this->middleware() в контроллере
 *
 * class SomeController extends Controller {
 *     public function __construct() {
 *         $this->middleware('auth:sanctum');          // ❌ ЗАПРЕЩЕНО!
 *         $this->middleware('fraud-check');           // ❌ ЗАПРЕЩЕНО!
 *         $this->middleware('rate-limit');            // ❌ ЗАПРЕЩЕНО!
 *     }
 * }
 *
 * Почему запрещено?
 * 1. Middleware должна быть ВИДНА в routing слое (clarity)
 * 2. Трудно отладить проблемы с middleware
 * 3. Невозможно использовать middleware группы
 * 4. Нарушает канон 2026 (centralized security)
 */
