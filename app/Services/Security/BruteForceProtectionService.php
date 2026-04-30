<?php

declare(strict_types=1);

namespace App\Services\Security;

use FraudMLService;

use LogManager;

use HttpFactory;

use CacheManager;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Events\BruteForceDetected;
use App\Models\BruteForceAttempt;
use App\Models\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use App\Services\Fraud\FraudMLService;
use Carbon\CarbonImmutable;

final class BruteForceProtectionService
{
    private const REDIS_PREFIX = 'security:brute_force:';

    private const HIBP_API_URL = 'https://api.pwnedpasswords.com/range/';

    private const HIBP_TIMEOUT_SECONDS = 5;

    private const MAX_ATTEMPTS_IN_CACHE = 100;

    private const CACHE_TTL_HOURS = 24;

    private const VELOCITY_CHECK_MINUTES = 10;

    private const VELOCITY_UNIQUE_IPS_THRESHOLD = 3;

    private const VELOCITY_FREQUENCY_THRESHOLD = 10;

    public function __construct(private readonly FraudMLService $fraudMLService,
        private readonly LogManager $logManager,
        private readonly HttpFactory $httpFactory,
        private readonly CacheManager $cacheManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly string $ipAddress,
        private readonly ?string $email,
        private readonly ?string $deviceFingerprint,
        private readonly ?string $userAgent,
        private readonly CacheManager $cache,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly FraudMLService $fraudML,) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            ipAddress: $request->ip(),
            email: $request->input('email'),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            userAgent: $request->userAgent(),
            cache: $this->cacheManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */,
            http: $this->httpFactory /* TODO: inject via constructor DI */ /* TODO: inject via DI */,
            log: $this->logManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */,
            fraudML: $this->fraudMLService /* TODO: inject via constructor DI */ /* TODO: inject via DI */,
        );
    }

    /**
     * Check login attempt and determine if it should be blocked
     */
    public function checkLoginAttempt(?User $user = null): BruteForceCheckResult
    {
        $attemptType = 'login';
        $config = config('security.brute_force.login', []);

        // Check rate limits (IP, Email, Device)
        $ipLimit = $this->checkRateLimit('ip', $this->ipAddress, $config['max_attempts'] ?? 5, $config['window_minutes'] ?? 5);
        if (! $ipLimit->allowed) {
            return $this->blockAttempt($user, $attemptType, 'rate_limit_ip', $ipLimit->remainingSeconds);
        }

        if ($this->email) {
            $emailLimit = $this->checkRateLimit('email', $this->email, $config['max_attempts'] ?? 5, $config['window_minutes'] ?? 5);
            if (! $emailLimit->allowed) {
                return $this->blockAttempt($user, $attemptType, 'rate_limit_email', $emailLimit->remainingSeconds);
            }
        }

        if ($this->deviceFingerprint) {
            $deviceLimit = $this->checkRateLimit('device', $this->deviceFingerprint, $config['max_attempts'] ?? 5, $config['window_minutes'] ?? 5);
            if (! $deviceLimit->allowed) {
                return $this->blockAttempt($user, $attemptType, 'rate_limit_device', $deviceLimit->remainingSeconds);
            }
        }

        // Check if user account is locked
        if ($user && $user->isLockedOut()) {
            return BruteForceCheckResult::blocked(
                reason: 'account_locked',
                remainingSeconds: $user->locked_until->diffInSeconds(CarbonImmutable::now()),
                user: $user,
            );
        }

        // Check HIBP if password provided
        $password = request()->input('password');
        if ($password && config('security.brute_force.enable_hibp', true)) {
            $hibpCheck = $this->checkPasswordHibp($password);
            if (! $hibpCheck->safe) {
                return $this->blockAttempt($user, $attemptType, 'hibp', 0, $hibpCheck->occurrences);
            }
        }

        // Check velocity (ML-based)
        if (config('security.brute_force.enable_velocity_check', true) && $user) {
            $velocityCheck = $this->checkVelocity($user);
            if (! $velocityCheck->allowed) {
                return $this->blockAttempt($user, $attemptType, 'velocity', 0, metadata: $velocityCheck->metadata);
            }
        }

        return BruteForceCheckResult::allowed();
    }

    /**
     * Record a successful login attempt
     */
    public function recordSuccess(?User $user = null, string $attemptType = 'login'): void
    {
        $this->clearRateLimits();

        if ($user) {
            $user->resetFailedLogins();
        }

        $this->logAttempt($user, $attemptType, wasSuccessful: true);
    }

    /**
     * Record a failed login attempt
     */
    public function recordFailure(?User $user = null, string $attemptType = 'login'): void
    {
        $this->incrementRateLimits();

        if ($user) {
            $user->recordFailedLogin();

            // Lock account after threshold
            $config = config('security.brute_force.login', []);
            $lockThreshold = $config['lock_after_attempts'] ?? 5;
            $lockDuration = $config['lock_duration_minutes'] ?? 60;

            if ($user->failed_login_count >= $lockThreshold) {
                $user->lockAccount($lockDuration);

                $this->eventDispatcher->dispatch(new BruteForceDetected(
                    type: 'account_lockout',
                    user: $user,
                    ipAddress: $this->ipAddress,
                    email: $this->email,
                    metadata: ['failed_attempts' => $user->failed_login_count],
                ));
            }
        }

        $this->logAttempt($user, $attemptType, wasSuccessful: false);
    }

    /**
     * Check rate limit using Redis sliding window
     */
    private function checkRateLimit(string $type, string $identifier, int $maxAttempts, int $windowMinutes): RateLimitResult
    {
        $key = self::REDIS_PREFIX."{$type}:{$identifier}";
        $now = CarbonImmutable::now()->timestamp;
        $windowStart = $now - ($windowMinutes * 60);

        // Get existing attempts in window
        $attempts = $this->cache->get($key, []);
        $attempts = array_filter($attempts, fn ($timestamp) => $timestamp > $windowStart);

        $count = count($attempts);

        if ($count >= $maxAttempts) {
            // Calculate TTL (oldest attempt in window)
            $oldestAttempt = min($attempts);
            $remainingSeconds = $oldestAttempt - $windowStart;

            return RateLimitResult::blocked($remainingSeconds);
        }

        return RateLimitResult::allowed($maxAttempts - $count);
    }

    /**
     * Increment rate limit counters
     */
    private function incrementRateLimits(): void
    {
        $now = CarbonImmutable::now()->timestamp;

        $this->incrementRateLimit('ip', $this->ipAddress);

        if ($this->email) {
            $this->incrementRateLimit('email', $this->email);
        }

        if ($this->deviceFingerprint) {
            $this->incrementRateLimit('device', $this->deviceFingerprint);
        }
    }

    private function incrementRateLimit(string $type, string $identifier): void
    {
        $key = self::REDIS_PREFIX."{$type}:{$identifier}";
        $attempts = $this->cache->get($key, []);
        $attempts[] = CarbonImmutable::now()->timestamp;

        // Keep only last N attempts to prevent memory bloat
        if (count($attempts) > self::MAX_ATTEMPTS_IN_CACHE) {
            $attempts = array_slice($attempts, -self::MAX_ATTEMPTS_IN_CACHE);
        }

        $this->cache->put($key, $attempts, CarbonImmutable::now()->addHours(self::CACHE_TTL_HOURS));
    }

    /**
     * Clear rate limit counters on successful attempt
     */
    private function clearRateLimits(): void
    {
        $this->clearRateLimit('ip', $this->ipAddress);

        if ($this->email) {
            $this->clearRateLimit('email', $this->email);
        }

        if ($this->deviceFingerprint) {
            $this->clearRateLimit('device', $this->deviceFingerprint);
        }
    }

    private function clearRateLimit(string $type, string $identifier): void
    {
        $key = self::REDIS_PREFIX."{$type}:{$identifier}";
        $this->cache->forget($key);
    }

    /**
     * Check password against HIBP database
     */
    private function checkPasswordHibp(string $password): HibpCheckResult
    {
        try {
            $hash = sha1($password);
            $prefix = substr($hash, 0, 5);
            $suffix = strtoupper(substr($hash, 5));

            $response = $this->http->timeout(self::HIBP_TIMEOUT_SECONDS)->get(self::HIBP_API_URL.$prefix);

            if (! $response->successful()) {
                // Fail open if HIBP is unavailable
                return HibpCheckResult::safe();
            }

            $hashes = explode("\n", $response->body());

            foreach ($hashes as $hash) {
                [$hashSuffix, $count] = explode(':', $hash);
                if ($hashSuffix === $suffix) {
                    return HibpCheckResult::compromised((int) $count);
                }
            }

            return HibpCheckResult::safe();
        } catch (\Exception $e) {
            $this->log->warning('HIBP check failed', ['error' => $e->getMessage()]);

            return HibpCheckResult::safe();
        }
    }

    /**
     * Check velocity patterns (ML-based fraud detection)
     */
    private function checkVelocity(User $user): VelocityCheckResult
    {
        // Get recent attempts for this user
        $recentAttempts = BruteForceAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subMinutes(self::VELOCITY_CHECK_MINUTES))
            ->get();

        // Check for multiple IPs in short time
        $uniqueIps = $recentAttempts->pluck('ip_address')->unique()->count();
        if ($uniqueIps >= self::VELOCITY_UNIQUE_IPS_THRESHOLD) {
            return VelocityCheckResult::blocked(
                reason: 'multiple_ips',
                metadata: ['unique_ips' => $uniqueIps, 'attempts' => $recentAttempts->count()],
            );
        }

        // Check for high frequency attempts
        if ($recentAttempts->count() >= self::VELOCITY_FREQUENCY_THRESHOLD) {
            return VelocityCheckResult::blocked(
                reason: 'high_frequency',
                metadata: ['attempts' => $recentAttempts->count()],
            );
        }

        // Integrate with FraudMLService for advanced detection
        $fraudCheck = $this->fraudML->analyzeLoginAttempt([
            'user_id' => $user->id,
            'ip_address' => $this->ipAddress,
            'device_fingerprint' => $this->deviceFingerprint,
            'attempts_in_window' => $recentAttempts->count(),
        ]);

        if ($fraudCheck && $fraudCheck['fraud_score'] > 0.8) {
            return VelocityCheckResult::blocked(
                reason: 'fraud_ml',
                metadata: ['fraud_score' => $fraudCheck['fraud_score']],
            );
        }

        return VelocityCheckResult::allowed();
    }

    /**
     * Block attempt and log it
     */
    private function blockAttempt(
        ?User $user,
        string $attemptType,
        string $reason,
        int $remainingSeconds,
        int $hibpOccurrences = 0,
        array $metadata = []
    ): BruteForceCheckResult {
        $this->logAttempt($user, $attemptType, wasSuccessful: false, wasBlocked: true, blockReason: $reason);

        $this->eventDispatcher->dispatch(new BruteForceDetected(
            type: $reason,
            user: $user,
            ipAddress: $this->ipAddress,
            email: $this->email,
            metadata: array_merge($metadata, [
                'remaining_seconds' => $remainingSeconds,
                'hibp_occurrences' => $hibpOccurrences,
            ]),
        ));

        return BruteForceCheckResult::blocked($reason, $remainingSeconds, $user);
    }

    /**
     * Log attempt to database
     */
    private function logAttempt(
        ?User $user,
        string $attemptType,
        bool $wasSuccessful,
        bool $wasBlocked = false,
        ?string $blockReason = null
    ): void {
        BruteForceAttempt::create([
            'user_id' => $user?->id,
            'email' => $this->email,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_fingerprint' => $this->deviceFingerprint,
            'attempt_type' => $attemptType,
            'was_successful' => $wasSuccessful,
            'was_blocked' => $wasBlocked,
            'block_reason' => $blockReason,
            'tenant_id' => $user?->tenant_id,
            'metadata' => [
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ],
        ]);
    }
}

// ========================
// DTOs
// ========================

final class BruteForceCheckResult
{
    public static function allowed(): self
    {
        return new self(allowed: true);
    }

    public static function blocked(string $reason, int $remainingSeconds, ?User $user = null): self
    {
        return new self(allowed: false, reason: $reason, remainingSeconds: $remainingSeconds, user: $user);
    }

    public function getErrorMessage(): string
    {
        return match ($this->reason) {
            'rate_limit_ip', 'rate_limit_email', 'rate_limit_device' => "Too many attempts. Please wait {$this->remainingSeconds} seconds.",
            'account_locked' => "Account locked. Please try again in {$this->remainingSeconds} seconds.",
            'hibp' => 'This password has been compromised in a data breach. Please choose a different password.',
            'velocity' => 'Suspicious activity detected. Please try again later.',
            'fraud_ml' => 'Security check failed. Please contact support.',
            default => 'Access denied for security reasons.',
        };
    }

    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly int $remainingSeconds = 0,
        public readonly ?User $user = null,
    ) {}
}

final class RateLimitResult
{
    public static function allowed(int $remainingAttempts): self
    {
        return new self(allowed: true, remainingAttempts: $remainingAttempts);
    }

    public static function blocked(int $remainingSeconds): self
    {
        return new self(allowed: false, remainingSeconds: $remainingSeconds);
    }

    private function __construct(
        public readonly bool $allowed,
        public readonly int $remainingAttempts = 0,
        public readonly int $remainingSeconds = 0,
    ) {}
}

final class HibpCheckResult
{
    public static function safe(): self
    {
        return new self(safe: true);
    }

    public static function compromised(int $occurrences): self
    {
        return new self(safe: false, occurrences: $occurrences);
    }

    private function __construct(
        public readonly bool $safe,
        public readonly int $occurrences = 0,
    ) {}
}

final class VelocityCheckResult
{
    public static function allowed(): self
    {
        return new self(allowed: true);
    }

    public static function blocked(string $reason, array $metadata = []): self
    {
        return new self(allowed: false, reason: $reason, metadata: $metadata);
    }

    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly array $metadata = [],
    ) {}
}
