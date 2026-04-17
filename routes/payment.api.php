<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Payment\PaymentController;

/**
 * Payment Gateway API Routes v1 — Production 2026 CANON
 * 
 * MIDDLEWARE ORDERING (CRITICAL):
 * 1. correlation-id      - Generate/validate X-Correlation-ID
 * 2. auth:sanctum        - API token validation
 * 3. tenant              - Tenant scoping
 * 4. idempotency-check   - Detect duplicate payments
 * 5. b2c-b2b             - Mode determination
 * 6. fraud-check         - STRICT fraud ML scoring
 * 7. rate-limit:10,1     - 10 req/min MAX (very strict)
 * 
 * Version: 2026.03.27
 */

// ===== PAYMENT INITIALIZATION & OPERATIONS (Authenticated) =====
Route::prefix('payments')
    ->middleware([
        'correlation-id',      // Inject X-Correlation-ID
        'auth:sanctum',        // Validate API token
        'tenant',              // Tenant scoping
        'idempotency-check',   // Prevent duplicate payments
        'b2c-b2b',             // B2C/B2B mode
        'fraud-check',         // STRICT fraud detection
        'payment.fraud.rate_limit', // Payment-specific rate limiting
        'rate-limit:10,1',     // 10 requests per minute MAX
    ])
    ->group(function () {
        // Payment initialization (создать платёж на холд)
        Route::post('/init', [PaymentController::class, 'init'])
            ->name('api.payments.init')
            ->middleware('throttle:5,1');  // Extra strict on init
        
        // Payment capture (списать со счёта)
        Route::post('/{payment}/capture', [PaymentController::class, 'capture'])
            ->name('api.payments.capture')
            ->middleware('throttle:5,1');  // Extra strict on capture
        
        // Payment refund (вернуть деньги)
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])
            ->name('api.payments.refund')
            ->middleware('throttle:3,1');  // Most strict on refund
        
        // Payment status check (read-only)
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('api.payments.show');

        // List user's payments (read-only)
        Route::get('/', [PaymentController::class, 'index'])
            ->name('api.payments.index');
    });

// ===== WEBHOOK ROUTES (No Auth, IP Whitelisted, Signature Verified) =====
Route::prefix('webhooks')
    ->middleware([
        'correlation-id',
        'webhook-signature',   // HMAC-SHA256 verification
        'ip-whitelist',        // Only from payment gateways
        'throttle:100,1',      // High rate limit for webhooks (no auth)
    ])
    ->group(function () {
        // Tinkoff payment gateway webhook
        Route::post('/tinkoff', [PaymentController::class, 'webhookTinkoff'])
            ->name('api.webhook.tinkoff')
            ->withoutMiddleware(['auth:sanctum', 'tenant']);  // Webhooks don't have auth
        
        // Tochka Bank webhook
        Route::post('/tochka', [PaymentController::class, 'webhookTochka'])
            ->name('api.webhook.tochka')
            ->withoutMiddleware(['auth:sanctum', 'tenant']);
        
        // Sber webhook
        Route::post('/sber', [PaymentController::class, 'webhookSber'])
            ->name('api.webhook.sber')
            ->withoutMiddleware(['auth:sanctum', 'tenant']);

        // SBP (Система быстрых платежей) webhook
        Route::post('/sbp', [PaymentController::class, 'webhookSbp'])
            ->name('api.webhook.sbp')
            ->withoutMiddleware(['auth:sanctum', 'tenant']);
    });

/**
 * PAYMENT ARCHITECTURE NOTES (PRODUCTION-READY 2026):
 * 
 * 1. IDEMPOTENCY CHECK (prevent duplicate payments):
 *    - Payload hash stored in payment_idempotency_records
 *    - Expires after 24 hours
 *    - Same payload_hash = return cached response
 * 
 * 2. FRAUD DETECTION (Multi-layer):
 *    - ML-score > threshold → block
 *    - Duplicate card attempts → block
 *    - High amount + new device → review
 *    - 3+ failed attempts → block
 * 
 * 3. RATE LIMITING (Tenant-aware):
 *    - Payment init: 5/min per tenant
 *    - Payment capture: 5/min per tenant
 *    - Payment refund: 3/min per tenant (strictest)
 *    - Global: 10/min per tenant
 * 
 * 4. WEBHOOK SECURITY:
 *    - HMAC-SHA256 signature verification
 *    - IP whitelist for payment gateways
 *    - Idempotent processing
 *    - Retry logic (exponential backoff)
 * 
 * 5. WALLET INTEGRATION:
 *    - After capture → WalletService::credit()
 *    - After refund → WalletService::credit()
 *    - All operations in DB::transaction()
 * 
 * 6. AUDIT LOGGING:
 *    - All payments logged with correlation_id
 *    - Fraud attempts logged to fraud_alert channel
 *    - 3-year retention
 */

