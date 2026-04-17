<?php declare(strict_types=1);

namespace App\Http;


use Illuminate\Auth\AuthManager;
use App\Http\Middleware\RoleBasedAccess;
use App\Http\Middleware\TenantCRMOnly;
use App\Http\Middleware\TenantScoping;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

final class Kernel extends HttpKernel
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            TenantScoping::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            TenantScoping::class,
        ],

        'tenant' => [
            TenantScoping::class,
            TenantCRMOnly::class,
        ],

        'tenant-admin' => [
            TenantScoping::class,
            TenantCRMOnly::class,
            RoleBasedAccess::class.':owner,manager',
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used to conveniently apply middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'payment.fraud.rate_limit' => \App\Http\Middleware\PaymentFraudRateLimitMiddleware::class,

        // ===== CORE MIDDLEWARE (API v1 Standard Order) =====
        'correlation-id'        => \App\Http\Middleware\CorrelationIdMiddleware::class,  // 1st - Always first
        'idempotency-check'     => \App\Http\Middleware\IdempotencyCheckMiddleware::class,  // 4th - Before fraud-check
        'tenant'                => TenantScoping::class,  // 3rd
        'b2c-b2b'               => \App\Http\Middleware\B2CB2BMiddleware::class,  // 5th
        'fraud-check'           => \App\Http\Middleware\FraudCheckMiddleware::class,  // 6th
        'rate-limit'            => \App\Http\Middleware\RateLimitingMiddleware::class,  // 7th
        'age-verify'            => \App\Http\Middleware\AgeVerificationMiddleware::class,  // 8th - Last

        // ===== PAYMENT-SPECIFIC RATE LIMITS =====
        'rate-limit:10,1'       => \App\Http\Middleware\RateLimitingMiddleware::class.':10,1',
        'rate-limit:5,1'        => \App\Http\Middleware\RateLimitingMiddleware::class.':5,1',
        'rate-limit:3,1'        => \App\Http\Middleware\RateLimitingMiddleware::class.':3,1',
        'rate-limit:50,1'       => \App\Http\Middleware\RateLimitingMiddleware::class.':50,1',
        'rate-limit:100,1'      => \App\Http\Middleware\RateLimitingMiddleware::class.':100,1',
        'rate-limit:1000,1'     => \App\Http\Middleware\RateLimitingMiddleware::class.':1000,1',

        // ===== LEGACY/ADDITIONAL MIDDLEWARE =====
        'two-factor' => \App\Http\Middleware\TwoFactorAuthentication::class,
        'business-guard' => \App\Http\Middleware\BusinessGroupGuard::class,
        'fraud-control' => \App\Http\Middleware\FraudControlMiddleware::class,
        'tenant-scoping' => TenantScoping::class,
        'tenant-crm-only' => TenantCRMOnly::class,
        'role-based-access' => RoleBasedAccess::class,
        'cors-secure' => \App\Http\Middleware\CorsSecureMiddleware::class,
        'csrf-protection' => \App\Http\Middleware\CsrfProtectionMiddleware::class,
        'ip-whitelist' => \App\Http\Middleware\IpWhitelistMiddleware::class,
        'rate-limit-payment' => \App\Http\Middleware\RateLimitPaymentMiddleware::class,
        'rate-limit-promo' => \App\Http\Middleware\RateLimitPromoMiddleware::class,
        'rate-limit-search' => \App\Http\Middleware\RateLimitSearchMiddleware::class,
        'check-role' => \App\Http\Middleware\CheckRole::class,
        'api-version' => \App\Http\Middleware\EnsureApiVersion::class,
        'api-rate-limit' => \App\Http\Middleware\ApiRateLimiter::class,
        'api-key-auth' => \App\Http\Middleware\ApiKeyAuthentication::class,
        'business-crm' => \App\Http\Middleware\BusinessCRMMiddleware::class,
        'validate-webhook' => \App\Http\Middleware\ValidateWebhookSignature::class,
        'rate-limit-auth' => \App\Http\Middleware\RateLimitingMiddleware::class.':auth',
        'b2c-b2b-cache' => \App\Http\Middleware\B2CB2BCacheMiddleware::class,
        'response-cache' => \App\Http\Middleware\ResponseCacheMiddleware::class,
        'user-taste-cache' => \App\Http\Middleware\UserTasteCacheMiddleware::class,
        'enrich-context' => \App\Http\Middleware\EnrichRequestContextMiddleware::class,
        'webhook-signature' => \App\Http\Middleware\WebhookSignatureMiddleware::class,
    ];
}
