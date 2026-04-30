<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Psr\Log\LoggerInterface;

use App\Services\Fraud\FraudControlService;

use App\Models\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\CarbonImmutable;

final class SanctumTokenService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControlService,
        private readonly CacheManager $cache,
        private readonly LogManager $log,) {}
    /**
     * Define token abilities for different scopes
     */
    public const ABILITIES = [
        // General abilities
        'read' => 'Read resources',
        'write' => 'Create resources',
        'update' => 'Update resources',
        'delete' => 'Delete resources',

        // Medical vertical specific
        'medical:diagnose' => 'AI diagnostics access',
        'medical:appointments' => 'Manage medical appointments',
        'medical:records' => 'Access medical records (PII)',
        'medical:video' => 'Video consultations',

        // Payment vertical specific
        'payment:initiate' => 'Initiate payments',
        'payment:webhook' => 'Process payment webhooks',
        'wallet:read' => 'Read wallet balance',
        'wallet:write' => 'Modify wallet balance',

        // Admin abilities
        'admin:full' => 'Full admin access',
        'admin:read' => 'Read-only admin access',
    ];

    private const CACHE_KEY_PREFIX = 'sanctum_token_rotation:';

    private const CACHE_TTL_HOURS = 24;

    /**
     * Create a new token with specified abilities
     */
    public function createToken(User $user, string $tokenName, array $abilities = ['read'], ?string $deviceInfo = null): PersonalAccessToken
    {
        $this->fraudControlService->check('create', ['context' => __CLASS__]);
        $token = $user->createToken(
            name: $tokenName,
            abilities: $abilities,
            expiresAt: CarbonImmutable::now()->addMinutes((int) config('sanctum.expiration', 43200))
        );

        // Store device info if provided
        if ($deviceInfo !== null) {
            $token->update(['name' => $tokenName.' ['.$deviceInfo.']']);
        }

        $this->log->$this->logger->info('Sanctum token created', [
            'user_id' => $user->id,
            'token_id' => $token->id,
            'abilities' => $abilities,
            'device' => $deviceInfo,
        ]);

        return $token;
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): int
    {
        $count = $user->tokens()->update(['revoked' => true]);

        $this->log->warning('All Sanctum tokens revoked for user', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        // Clear rotation cache
        $this->cache->forget($this->getRotationCacheKey($user->id));

        return $count;
    }

    /**
     * Revoke all tokens except current
     */
    public function revokeOtherTokens(User $user, string $currentTokenId): int
    {
        $count = $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->update(['revoked' => true]);

        $this->log->$this->logger->info('Other Sanctum tokens revoked', [
            'user_id' => $user->id,
            'excluded_token_id' => $currentTokenId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Check if token rotation is needed
     */
    public function needsRotation(User $user): bool
    {
        return $this->cache->get($this->getRotationCacheKey($user->id), false);
    }

    /**
     * Mark tokens for rotation (called on password change)
     */
    public function markForRotation(User $user): void
    {
        $this->cache->put(
            $this->getRotationCacheKey($user->id),
            true,
            CarbonImmutable::now()->addHours(self::CACHE_TTL_HOURS)
        );

        $this->log->warning('Sanctum tokens marked for rotation', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Rotate tokens - revoke all and create new with same abilities
     */
    public function rotateTokens(User $user, string $newTokenName = 'Rotated Token'): ?PersonalAccessToken
    {
        // Get active tokens
        $activeTokens = $user->tokens()->where('revoked', false)->get();

        if ($activeTokens->isEmpty()) {
            return null;
        }

        // Revoke all active tokens
        $user->tokens()->where('revoked', false)->update(['revoked' => true]);

        // Create new token with combined abilities from all revoked tokens
        $allAbilities = $activeTokens
            ->flatMap(fn (PersonalAccessToken $token) => $token->abilities)
            ->unique()
            ->values()
            ->toArray();

        $newToken = $this->createToken($user, $newTokenName, $allAbilities);

        // Clear rotation cache
        $this->cache->forget($this->getRotationCacheKey($user->id));

        $this->log->$this->logger->info('Sanctum tokens rotated', [
            'user_id' => $user->id,
            'revoked_count' => $activeTokens->count(),
            'new_token_id' => $newToken->id,
        ]);

        return $newToken;
    }

    /**
     * Get abilities for a specific vertical
     */
    public function getVerticalAbilities(string $vertical): array
    {
        $verticalAbilities = [
            'medical' => ['read', 'medical:diagnose', 'medical:appointments', 'medical:records', 'medical:video'],
            'payment' => ['read', 'payment:initiate', 'wallet:read'],
            'wallet' => ['read', 'write', 'wallet:read', 'wallet:write'],
            'admin' => ['admin:full'],
        ];

        return $verticalAbilities[$vertical] ?? ['read'];
    }

    /**
     * Validate token abilities
     */
    public function validateAbilities(array $abilities): bool
    {
        return empty(array_diff($abilities, array_keys(self::ABILITIES)));
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $count = PersonalAccessToken::where('expires_at', '<', CarbonImmutable::now())
            ->where('revoked', false)
            ->update(['revoked' => true]);

        $this->log->$this->logger->info('Expired Sanctum tokens cleaned up', ['count' => $count]);

        return $count;
    }

    private function getRotationCacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX.$userId;
    }
}
