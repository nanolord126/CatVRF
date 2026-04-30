<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Auth\AuthManager;
use App\Http\Middleware\RoleBasedAccess;
use App\Http\Middleware\TenantCRMOnly;
use App\Http\Middleware\TenantScoping;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Middleware\SplitKeyValidationMiddleware;
use App\Http\Middleware\ApiKeyAuthentication;
use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\B2CB2BCacheMiddleware;
use App\Http\Middleware\B2CB2BMiddleware;
use App\Http\Middleware\AgeVerificationMiddleware;
use App\Http\Middleware\BruteForceProtectionMiddleware;
use App\Http\Middleware\BusinessCRMMiddleware;
use App\Http\Middleware\BusinessGroupGuard;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\CorsSecureMiddleware;
use App\Http\Middleware\CsrfProtectionMiddleware;
use App\Http\Middleware\VpnProtectionMiddleware;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnrichRequestContextMiddleware;
use App\Http\Middleware\EnsureApiVersion;
use App\Http\Middleware\FraudCheckMiddleware;
use App\Http\Middleware\FraudControlMiddleware;
use App\Http\Middleware\IdempotencyCheckMiddleware;
use App\Http\Middleware\IpWhitelistMiddleware;
use App\Http\Middleware\PaymentFraudRateLimitMiddleware;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RateLimitPaymentMiddleware;
use App\Http\Middleware\RateLimitPromoMiddleware;
use App\Http\Middleware\RateLimitSearchMiddleware;
use App\Http\Middleware\RateLimitingMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ResponseCacheMiddleware;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\TwoFactorAuthentication;
use App\Http\Middleware\UserTasteCacheMiddleware;
use App\Http\Middleware\ValidateWebhookSignature;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\WebhookSignatureMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

final class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            TenantScoping::class,
        ],

        'api' => [
            EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
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
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'precognitive' => HandlePrecognitiveRequests::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'payment.fraud.rate_limit' => PaymentFraudRateLimitMiddleware::class,
        'brute.force' => BruteForceProtectionMiddleware::class,
        'vpn.protection' => VpnProtectionMiddleware::class,

        // ===== CORE MIDDLEWARE (API v1 Standard Order) =====
        'correlation-id'        => CorrelationIdMiddleware::class,  // 1st - Always first
        'idempotency-check'     => IdempotencyCheckMiddleware::class,  // 4th - Before fraud-check
        'tenant'                => TenantScoping::class,  // 3rd
        'b2c-b2b'               => B2CB2BMiddleware::class,  // 5th
        'fraud-check'           => FraudCheckMiddleware::class,  // 6th
        'rate-limit'            => RateLimitingMiddleware::class,  // 7th
        'age-verify'            => AgeVerificationMiddleware::class,  // 8th - Last
        'cooldown-check'        => CheckCooldownMiddleware::class,  // Check cooldown before financial operations

        // ===== PAYMENT-SPECIFIC RATE LIMITS =====
        'rate-limit:10,1'       => RateLimitingMiddleware::class.':10,1',
        'rate-limit:5,1'        => RateLimitingMiddleware::class.':5,1',
        'rate-limit:3,1'        => RateLimitingMiddleware::class.':3,1',
        'rate-limit:50,1'       => RateLimitingMiddleware::class.':50,1',
        'rate-limit:100,1'      => RateLimitingMiddleware::class.':100,1',
        'rate-limit:1000,1'     => RateLimitingMiddleware::class.':1000,1',

        // ===== LEGACY/ADDITIONAL MIDDLEWARE =====
        'two-factor' => TwoFactorAuthentication::class,
        'business-guard' => BusinessGroupGuard::class,
        'fraud-control' => FraudControlMiddleware::class,
        'tenant-scoping' => TenantScoping::class,
        'tenant-crm-only' => TenantCRMOnly::class,
        'role-based-access' => RoleBasedAccess::class,
        'cors-secure' => CorsSecureMiddleware::class,
        'csrf-protection' => CsrfProtectionMiddleware::class,
        'ip-whitelist' => IpWhitelistMiddleware::class,
        'rate-limit-payment' => RateLimitPaymentMiddleware::class,
        'rate-limit-promo' => RateLimitPromoMiddleware::class,
        'rate-limit-search' => RateLimitSearchMiddleware::class,
        'check-role' => CheckRole::class,
        'api-version' => EnsureApiVersion::class,
        'api-rate-limit' => ApiRateLimiter::class,
        'api-key-auth' => ApiKeyAuthentication::class,
        'business-crm' => BusinessCRMMiddleware::class,
        'validate-webhook' => ValidateWebhookSignature::class,
        'rate-limit-auth' => RateLimitingMiddleware::class.':auth',
        'b2c-b2b-cache' => B2CB2BCacheMiddleware::class,
        'response-cache' => ResponseCacheMiddleware::class,
        'user-taste-cache' => UserTasteCacheMiddleware::class,
        'enrich-context' => EnrichRequestContextMiddleware::class,
        'webhook-signature' => WebhookSignatureMiddleware::class,
        'split-key' => SplitKeyValidationMiddleware::class,
        'b2b.verified' => \App\Http\Middleware\B2BVerified::class,
    ];

    public function __construct(
        private readonly AuthManager $authManager,
    ) {}
}
}
