<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\ServiceAccount;
use App\Services\Audit\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Carbon\CarbonImmutable;

final class M2MAuthenticationService
{
    private const TOKEN_EXPIRY_HOURS = 1;

    private const CACHE_TTL_MINUTES = 5;

    private readonly Configuration $jwtConfig;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly CacheRepository $cache,
    ) {
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(config('app.key'))
        );
    }

    /**
     * Authenticate service account using OAuth2 client credentials
     */
    public function authenticate(string $clientId, string $clientSecret, ?string $fingerprint = null): ?ServiceAccount
    {
        $account = ServiceAccount::byClientId($clientId)->first();

        if (! $account) {
            return null;
        }

        if (! $account->isValid()) {
            return null;
        }

        // Verify client secret
        if (! $account->verifyClientSecret($clientSecret)) {
            return null;
        }

        // Verify mTLS certificate fingerprint if provided
        if ($fingerprint && ! $this->verifyCertificate($account, $fingerprint)) {
            return null;
        }

        // Update last used timestamp
        $account->updateLastUsed();

        return $account;
    }

    /**
     * Issue JWT token for service account
     */
    public function issueToken(ServiceAccount $account): string
    {
        $correlationId = Str::uuid()->toString();

        $this->fraudControl->check(
            userId: null,
            operationType: 'm2m_token_issuance',
            amount: 0,
            correlationId: $correlationId,
        );

        $now = new \DateTimeImmutable();

        $builder = $this->jwtConfig->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor(config('app.url'))
            ->identifiedBy($account->account_id)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+'.self::TOKEN_EXPIRY_HOURS.' hours'))
            ->withClaim('sub', $account->client_id)
            ->withClaim('type', 'm2m')
            ->withClaim('tenant_id', $account->tenant_id)
            ->withClaim('scopes', $account->scopes)
            ->withClaim('permissions', $account->permissions)
            ->withClaim('account_name', $account->name);

        $token = $builder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        // Audit log
        $this->audit->record(
            action: 'm2m_token_issued',
            subjectType: ServiceAccount::class,
            subjectId: $account->id,
            newValues: [
                'client_id' => $account->client_id,
                'account_name' => $account->name,
                'expires_at' => $token->claims()->get('exp'),
            ],
            correlationId: $correlationId,
        );

        return $token->toString();
    }

    /**
     * Validate JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $parsedToken = $this->jwtConfig->parser()->parse($token);

            $constraints = $this->jwtConfig->validationConstraints();

            if (! $this->jwtConfig->validator()->validate($parsedToken, ...$constraints)) {
                return null;
            }

            $claims = $parsedToken->claims();

            return [
                'valid' => true,
                'account_id' => $claims->get('jti'),
                'client_id' => $claims->get('sub'),
                'type' => $claims->get('type'),
                'tenant_id' => $claims->get('tenant_id'),
                'scopes' => $claims->get('scopes'),
                'permissions' => $claims->get('permissions'),
                'account_name' => $claims->get('account_name'),
                'expires_at' => $claims->get('exp'),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create a new service account
     */
    public function createServiceAccount(array $data): ServiceAccount
    {
        $correlationId = Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $data['created_by'] ?? null,
            operationType: 'service_account_creation',
            amount: 0,
            correlationId: $correlationId,
        );

        $clientSecret = $this->generateClientSecret();

        $account = ServiceAccount::create([
            'account_id' => Str::uuid()->toString(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'client_id' => $this->generateClientId(),
            'client_secret' => $clientSecret,
            'certificate_fingerprint' => $data['certificate_fingerprint'] ?? null,
            'certificate_pem' => $data['certificate_pem'] ?? null,
            'scopes' => $data['scopes'] ?? [],
            'permissions' => $data['permissions'] ?? [],
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? CarbonImmutable::now()->addYears(1),
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->record(
            action: 'service_account_created',
            subjectType: ServiceAccount::class,
            subjectId: $account->id,
            newValues: [
                'name' => $account->name,
                'client_id' => $account->client_id,
                'tenant_id' => $account->tenant_id,
                'scopes' => $account->scopes,
            ],
            correlationId: $correlationId,
        );

        return $account;
    }

    /**
     * Rotate client secret
     */
    public function rotateClientSecret(int $accountId, string $rotatedBy): ServiceAccount
    {
        $account = ServiceAccount::findOrFail($accountId);
        $newSecret = $this->generateClientSecret();

        $account->update([
            'client_secret' => $newSecret,
        ]);

        // Audit log
        $this->audit->record(
            action: 'service_account_secret_rotated',
            subjectType: ServiceAccount::class,
            subjectId: $account->id,
            newValues: [
                'rotated_by' => $rotatedBy,
            ],
        );

        return $account->fresh();
    }

    /**
     * Suspend service account
     */
    public function suspendServiceAccount(int $accountId, string $reason, string $suspendedBy): bool
    {
        $account = ServiceAccount::findOrFail($accountId);
        $suspended = $account->suspend($reason, $suspendedBy);

        if ($suspended) {
            $this->audit->record(
                action: 'service_account_suspended',
                subjectType: ServiceAccount::class,
                subjectId: $account->id,
                newValues: [
                    'suspended_by' => $suspendedBy,
                    'reason' => $reason,
                ],
            );
        }

        return $suspended;
    }

    /**
     * Revoke service account
     */
    public function revokeServiceAccount(int $accountId, string $reason, string $revokedBy): bool
    {
        $account = ServiceAccount::findOrFail($accountId);
        $revoked = $account->revoke($reason, $revokedBy);

        if ($revoked) {
            $this->audit->record(
                action: 'service_account_revoked',
                subjectType: ServiceAccount::class,
                subjectId: $account->id,
                newValues: [
                    'revoked_by' => $revokedBy,
                    'reason' => $reason,
                ],
            );
        }

        return $revoked;
    }

    /**
     * Get tenant service accounts
     */
    public function getTenantServiceAccounts(int $tenantId): array
    {
        return ServiceAccount::byTenant($tenantId)
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'account_id' => $account->account_id,
                'name' => $account->name,
                'client_id' => $account->client_id,
                'scopes' => $account->scopes,
                'status' => $account->status,
                'valid' => $account->isValid(),
                'last_used_at' => $account->last_used_at?->toISOString(),
                'expires_at' => $account->expires_at?->toISOString(),
            ])
            ->toArray();
    }

    /**
     * Check rate limit for service account
     */
    public function checkRateLimit(ServiceAccount $account, string $endpoint): bool
    {
        // Placeholder - integrate with rate limiting service
        // For AI agents, use higher limits
        $limits = [
            'ai_diagnostics' => 1000, // requests per hour
            'default' => 100, // requests per hour
        ];

        $limit = $limits[$endpoint] ?? $limits['default'];
        $key = "m2m_rate_limit:{$account->client_id}:{$endpoint}";

        $current = $this->cache->get($key, 0);

        if ($current >= $limit) {
            return false;
        }

        $this->cache->put($key, $current + 1, 3600);

        return true;
    }

    /**
     * Verify mTLS certificate fingerprint
     */
    private function verifyCertificate(ServiceAccount $account, string $fingerprint): bool
    {
        if (! $account->certificate_fingerprint) {
            return false;
        }

        return hash_equals($account->certificate_fingerprint, $fingerprint);
    }

    /**
     * Generate client ID
     */
    private function generateClientId(): string
    {
        return 'svc_'.Str::random(32);
    }

    /**
     * Generate client secret
     */
    private function generateClientSecret(): string
    {
        return Str::random(64);
    }
}
