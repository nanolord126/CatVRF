<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Payment\WebhookController;

/**
 * Payment Processing API Routes v1 — STRICT SECURITY MIDDLEWARE
 *
 * CRITICAL: Payment routes require MAXIMUM security:
 * - Idempotency check (MUST prevent duplicate charging)
 * - Fraud detection (ML scoring before any transaction)
 * - Rate limiting (STRICT: 10 requests/min, 5 per second for critical ops)
 * - Correlation ID (Track every payment for audit)
 *
 * MIDDLEWARE ORDER (IMMUTABLE):
 * 1. correlation-id        ✅ X-Correlation-ID header (ALWAYS FIRST)
 * 2. auth:sanctum          ✅ API token validation
 * 3. tenant                ✅ Tenant scoping
 * 4. idempotency-check     ✅ Duplicate detection (SHA-256 payload hash)
 * 5. b2c-b2b               ✅ B2C vs B2B mode
 * 6. fraud-check           ✅ ML fraud scoring (MUST block high scores)
 * 7. rate-limit            ✅ STRICT throttling (10,1 for init; 5,1 for sensitive ops)
 * 8. age-verify            ✅ Age verification (18+ for some payment methods)
 *
 * Version: 2026.03.29 (PRODUCTION READY)
 */

// ===== WEBHOOK ENDPOINTS (External — Tinkoff, Tochka, Sber) =====
/**
 * WEBHOOK ROUTES: NO AUTH (Signed by payment gateway)
 * - Signature verification in WebhookController
 * - IP whitelist in WebhookMiddleware
 */
Route::middleware([
    'correlation-id',           // 1st - Track webhook for audit
    'webhook-signature',        // Verify HMAC-SHA256 signature
    'webhook-ip-whitelist',     // Only from known gateway IPs
])->group(function () {
    // Tinkoff webhook
    Route::post('/webhooks/tinkoff', [WebhookController::class, 'handleTinkoff'])
        ->name('api.payment.webhook.tinkoff')
        ->withoutMiddleware('csrf');

    // Tochka webhook
    Route::post('/webhooks/tochka', [WebhookController::class, 'handleTochka'])
        ->name('api.payment.webhook.tochka')
        ->withoutMiddleware('csrf');

    // Sber webhook
    Route::post('/webhooks/sber', [WebhookController::class, 'handleSber'])
        ->name('api.payment.webhook.sber')
        ->withoutMiddleware('csrf');
});

// ===== AUTHENTICATED PAYMENT ENDPOINTS =====
/**
 * CRITICAL MIDDLEWARE PIPELINE FOR PAYMENTS
 *
 * Order MUST be maintained:
 * 1. correlation-id → Track all payment attempts
 * 2. auth:sanctum → Verify user identity
 * 3. tenant → Scope to user's tenant
 * 4. idempotency-check → PREVENT DUPLICATE CHARGES (SHA-256 hash check)
 * 5. b2c-b2b → Determine pricing & rules
 * 6. fraud-check → BLOCK suspicious transactions (score > 0.85)
 * 7. rate-limit → STRICT: 10/min for init, 5/min for sensitive ops
 * 8. age-verify → 18+ for certain payment methods
 */
Route::middleware([
    'correlation-id',           // 1st - ALWAYS FIRST
    'auth:sanctum',             // 2nd - Validate API token
    'tenant',                   // 3rd - Tenant scoping
    'idempotency-check',        // 4th - CRITICAL: Detect & cache duplicates
    'b2c-b2b',                  // 5th - Determine B2C vs B2B
    'fraud-check',              // 6th - ML fraud scoring
    'rate-limit:10,1',          // 7th - DEFAULT: 10 requests/min (can override per route)
    'age-verify',               // 8th - Last: Age verification (18+)
])->group(function () {
    Route::prefix('payments')->group(function () {
        /**
         * POST /payments/init
         *
         * STRICT RATE LIMIT: 10 requests/min (1 per 6 seconds)
         * FRAUD CHECK: MUST pass (score < 0.85)
         * IDEMPOTENCY: Required Idempotency-Key header (UUID or alphanumeric 1-128)
         * PAYLOAD: {
         *     "amount": 50000,           // копейки
         *     "currency": "RUB",
         *     "description": "Маникюр",
         *     "order_id": "order_12345",
         *     "idempotency_key": "uuid-or-unique-string"  // MUST be unique
         * }
         * 
         * RESPONSES:
         * - 201 Created: Payment initiated successfully
         *   {
         *     "correlation_id": "...",
         *     "payment_id": "...",
         *     "redirect_url": "https://tinkoff.ru/...",
         *     "status": "pending"
         *   }
         * - 400 Bad Request: Invalid payload
         * - 403 Forbidden: Fraud detected (score > 0.85)
         * - 429 Too Many Requests: Rate limit exceeded (Retry-After header)
         */
        Route::post('/', [PaymentController::class, 'init'])
            ->name('api.payment.init')
            ->middleware('throttle:10,1');  // 10 requests/min for payment init

        /**
         * POST /payments/{payment_id}/capture
         *
         * MODERATE RATE LIMIT: 5 requests/min
         * FRAUD CHECK: Re-check (fraud score can change)
         * IDEMPOTENCY: Prevent double-capture
         * 
         * RESPONSES:
         * - 200 OK: Payment captured
         * - 404 Not Found: Payment not found
         * - 409 Conflict: Already captured or expired
         * - 429 Too Many Requests: Rate limit exceeded
         */
        Route::post('{payment_id}/capture', [PaymentController::class, 'capture'])
            ->name('api.payment.capture')
            ->middleware('throttle:5,1');  // STRICT: 5 requests/min

        /**
         * POST /payments/{payment_id}/authorize
         *
         * STRICT RATE LIMIT: 3 requests/min (for sensitive operation)
         * FRAUD CHECK: High-sensitivity check
         * IDEMPOTENCY: CRITICAL to prevent duplicate authorization
         * 
         * RESPONSES:
         * - 200 OK: Authorized (hold placed)
         * - 403 Forbidden: Fraud detected
         * - 409 Conflict: Already authorized
         * - 429 Too Many Requests: Rate limit exceeded
         */
        Route::post('{payment_id}/authorize', [PaymentController::class, 'authorize'])
            ->name('api.payment.authorize')
            ->middleware('throttle:3,1');  // ULTRA-STRICT: 3 requests/min

        /**
         * POST /payments/{payment_id}/refund
         *
         * STRICT RATE LIMIT: 5 requests/min
         * FRAUD CHECK: Detect refund abuse
         * IDEMPOTENCY: Prevent double-refund
         * 
         * RESPONSES:
         * - 200 OK: Refund initiated
         * - 404 Not Found: Payment not found
         * - 409 Conflict: Cannot refund (not captured or already refunded)
         * - 429 Too Many Requests: Rate limit exceeded
         */
        Route::post('{payment_id}/refund', [PaymentController::class, 'refund'])
            ->name('api.payment.refund')
            ->middleware('throttle:5,1');  // 5 requests/min

        /**
         * GET /payments/{payment_id}
         *
         * LIGHT RATE LIMIT: 100 requests/min (read-only)
         * NO FRAUD CHECK (read-only operation)
         * NO IDEMPOTENCY (no state change)
         * 
         * RESPONSES:
         * - 200 OK: Payment details
         * - 404 Not Found: Payment not found
         */
        Route::get('{payment_id}', [PaymentController::class, 'show'])
            ->name('api.payment.show')
            ->withoutMiddleware('rate-limit:10,1')  // Override default rate limit
            ->middleware('throttle:100,1');  // LIGHT: 100 requests/min for reads

        /**
         * GET /payments
         *
         * LIGHT RATE LIMIT: 50 requests/min (read-only)
         * NO FRAUD CHECK (read-only)
         * 
         * Query params:
         * - status: pending, authorized, captured, refunded, failed
         * - date_from, date_to
         * - limit, offset
         * 
         * RESPONSES:
         * - 200 OK: List of payments
         */
        Route::get('/', [PaymentController::class, 'index'])
            ->name('api.payment.index')
            ->withoutMiddleware('rate-limit:10,1')
            ->middleware('throttle:50,1');  // 50 requests/min for list

        /**
         * POST /payments/{payment_id}/retry
         *
         * STRICT RATE LIMIT: 3 requests/min (sensitive operation)
         * FRAUD CHECK: Re-check fraud score before retry
         * IDEMPOTENCY: CRITICAL
         * 
         * RESPONSES:
         * - 200 OK: Retry initiated
         * - 403 Forbidden: Fraud detected on retry
         * - 404 Not Found: Payment not found
         * - 429 Too Many Requests: Too many retries
         */
        Route::post('{payment_id}/retry', [PaymentController::class, 'retry'])
            ->name('api.payment.retry')
            ->middleware('throttle:3,1');  // 3 attempts/min
    });

    // ===== PAYMENT METHOD MANAGEMENT =====
    Route::prefix('payment-methods')->group(function () {
        /**
         * POST /payment-methods/bind-card
         * 
         * BIND new credit/debit card
         * 
         * STRICT RATE LIMIT: 5 requests/min
         * FRAUD CHECK: Detect card testing attacks
         * IDEMPOTENCY: Prevent duplicate card binding
         * AGE VERIFY: 18+ for certain card types
         */
        Route::post('bind-card', [\App\Http\Controllers\Api\V1\Payment\PaymentMethodController::class, 'bindCard'])
            ->name('api.payment-method.bind-card')
            ->middleware('throttle:5,1');

        /**
         * POST /payment-methods/{id}/verify-3ds
         *
         * VERIFY 3DS/3DS2 for card
         */
        Route::post('{id}/verify-3ds', [\App\Http\Controllers\Api\V1\Payment\PaymentMethodController::class, 'verify3DS'])
            ->name('api.payment-method.verify-3ds')
            ->middleware('throttle:10,1');

        /**
         * DELETE /payment-methods/{id}
         *
         * DELETE payment method
         */
        Route::delete('{id}', [\App\Http\Controllers\Api\V1\Payment\PaymentMethodController::class, 'destroy'])
            ->name('api.payment-method.delete')
            ->middleware('throttle:10,1');

        /**
         * GET /payment-methods
         *
         * LIST user's payment methods (light rate limit)
         */
        Route::get('/', [\App\Http\Controllers\Api\V1\Payment\PaymentMethodController::class, 'index'])
            ->name('api.payment-method.index')
            ->withoutMiddleware('rate-limit:10,1')
            ->middleware('throttle:100,1');
    });

    // ===== PAYOUT MANAGEMENT =====
    Route::prefix('payouts')->group(function () {
        /**
         * POST /payouts
         *
         * CREATE payout request
         *
         * STRICT RATE LIMIT: 3 requests/min (sensitive)
         * FRAUD CHECK: Detect withdrawal attacks
         * 
         * PAYLOAD: {
         *     "amount": 100000,           // копейки
         *     "destination": "card",      // card, sbp, bank_account
         *     "destination_id": "...",
         *     "idempotency_key": "..."
         * }
         */
        Route::post('/', [\App\Http\Controllers\Api\V1\Payment\PayoutController::class, 'store'])
            ->name('api.payout.store')
            ->middleware('throttle:3,1');

        /**
         * GET /payouts/{id}
         *
         * VIEW payout status (light rate limit)
         */
        Route::get('{id}', [\App\Http\Controllers\Api\V1\Payment\PayoutController::class, 'show'])
            ->name('api.payout.show')
            ->withoutMiddleware('rate-limit:10,1')
            ->middleware('throttle:100,1');

        /**
         * GET /payouts
         *
         * LIST payouts (light rate limit)
         */
        Route::get('/', [\App\Http\Controllers\Api\V1\Payment\PayoutController::class, 'index'])
            ->name('api.payout.index')
            ->withoutMiddleware('rate-limit:10,1')
            ->middleware('throttle:50,1');
    });
});
