<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Models\UserDevice;
use App\Events\Security\PasswordChanged;
use App\Events\Security\TwoFactorChanged;
use App\Events\Security\NewDeviceLogin;
use App\Services\Fraud\FraudMLService;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class AuthService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly FraudMLService $fraudML,
        private readonly CooldownService $cooldownService,
        private readonly TwoFactorService $twoFactorService,
        private readonly DatabaseManager $db,
        private readonly Hasher $hash,
        private readonly LogManager $log,
        private readonly EventDispatcher $eventDispatcher,
        private readonly AuditService $audit,
    ) {}

    /**
     * Login user with email/password
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->firstOrFail();

        // ML Fraud Check (before authentication to prevent brute force)
        $correlationId = $credentials['correlation_id'] ?? Str::uuid()->toString();
        $fraudResult = $this->fraudML->predictRisk(
            userId: $user->id,
            operationType: 'login',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: $credentials['fingerprint'] ?? null,
            context: [
                'user_agent' => request()->userAgent(),
                'location_country' => $credentials['location_country'] ?? null,
                'location_city' => $credentials['location_city'] ?? null,
            ],
            correlationId: $correlationId,
        );

        // Handle ML decision
        if ($fraudResult['decision'] === 'block') {
            $this->applyFraudBlock($user, $fraudResult, $correlationId);
            throw ValidationException::withMessages([
                'email' => ['Login blocked due to suspicious activity. Please contact support.'],
            ]);
        }

        if ($fraudResult['decision'] === 'challenge') {
            $this->applyFraudChallenge($user, $fraudResult, $correlationId);
            // Force 2FA challenge even if not enabled
            return [
                'requires_2fa' => true,
                'user_id' => $user->id,
                'fraud_challenge' => true,
                'reason' => 'Suspicious activity detected',
            ];
        }

        if (! $user->canLogin()) {
            throw ValidationException::withMessages([
                'email' => ['Account is not active or has been blocked'],
            ]);
        }

        if (! $this->hash->check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        // Check if 2FA is enabled
        if ($user->two_factor_enabled) {
            return [
                'requires_2fa' => true,
                'user_id' => $user->id,
            ];
        }

        return $this->completeLogin($user, $credentials, $fraudResult);
    }

    /**
     * Apply fraud block actions
     */
    private function applyFraudBlock(User $user, array $fraudResult, string $correlationId): void
    {
        // Start fraud block cooldown
        $this->cooldownService->startCooldown(
            $user,
            \App\Enums\CooldownActionType::FRAUD_BLOCK,
            24, // 24 hours
            sprintf(
                'ML Fraud Detection: Score %.2f, %s',
                $fraudResult['score'],
                $fraudResult['explanation']['top_features'][0]['feature'] ?? 'unknown'
            ),
            $user->tenant_id,
            [
                'fraud_score' => $fraudResult['score'],
                'decision' => $fraudResult['decision'],
                'model_scores' => $fraudResult['model_scores'],
                'correlation_id' => $correlationId,
            ]
        );

        $this->log->channel('fraud_alert')->warning('Login blocked by ML fraud detection', [
            'user_id' => $user->id,
            'email' => $user->email,
            'fraud_score' => $fraudResult['score'],
            'decision' => $fraudResult['decision'],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Apply fraud challenge actions (soft Cooldown)
     */
    private function applyFraudChallenge(User $user, array $fraudResult, string $correlationId): void
    {
        // Start soft challenge cooldown
        $this->cooldownService->startCooldown(
            $user,
            \App\Enums\CooldownActionType::SOFT_CHALLENGE,
            1, // 1 hour
            sprintf(
                'ML Fraud Challenge: Score %.2f',
                $fraudResult['score']
            ),
            $user->tenant_id,
            [
                'fraud_score' => $fraudResult['score'],
                'decision' => $fraudResult['decision'],
                'correlation_id' => $correlationId,
            ]
        );

        $this->log->channel('fraud_alert')->info('Login challenged by ML fraud detection', [
            'user_id' => $user->id,
            'email' => $user->email,
            'fraud_score' => $fraudResult['score'],
            'decision' => $fraudResult['decision'],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Complete login after 2FA verification
     */
    public function completeLogin(User $user, array $credentials, ?array $fraudResult = null): array
    {
        return $this->db->transaction(function () use ($user, $credentials) {
            // Revoke old tokens for security
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken(
                $credentials['device_name'] ?? 'API Token',
                ['*'],
                CarbonImmutable::now()->addDays(30),
            );

            // Update user login info
            $user->update([
                'last_login_at' => CarbonImmutable::now(),
                'last_activity_at' => CarbonImmutable::now(),
            ]);

            // Register device
            $device = $this->registerDevice($user, $credentials);

            $this->log->channel('audit')->$this->logger->info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'device_id' => $device->id ?? null,
                'correlation_id' => $credentials['correlation_id'] ?? null,
                'fraud_score' => $fraudResult['score'] ?? null,
                'fraud_decision' => $fraudResult['decision'] ?? null,
            ]);

            return [
                'token' => $token->plainTextToken,
                'type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at,
                'user' => $user,
                'device' => $device,
            ];
        });
    }

    /**
     * Logout user
     */
    public function logout(User $user): bool
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        $this->log->channel('audit')->$this->logger->info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return true;
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(User $user): bool
    {
        $user->tokens()->delete();
        $user->revokeAllDevices();

        $this->log->channel('audit')->$this->logger->info('User logged out from all devices', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return true;
    }

    /**
     * Refresh token
     */
    public function refreshToken(User $user): array
    {
        $oldToken = $user->currentAccessToken();

        if ($oldToken) {
            $oldToken->delete();
        }

        $newToken = $user->createToken(
            'Refreshed Token',
            $oldToken?->abilities ?? ['*'],
            CarbonImmutable::now()->addDays(30),
        );

        $this->log->channel('audit')->$this->logger->info('Token refreshed', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'token' => $newToken->plainTextToken,
            'type' => 'Bearer',
            'expires_at' => $newToken->accessToken->expires_at,
        ];
    }

    /**
     * Get user devices
     */
    public function getUserDevices(User $user): Collection
    {
        return $user->devices()->active()->latest('last_used_at')->get();
    }

    /**
     * Revoke device
     */
    public function revokeDevice(User $user, int $deviceId): bool
    {
        $device = $user->devices()->findOrFail($deviceId);
        $device->revoke();

        $this->log->channel('audit')->$this->logger->info('Device revoked', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
        ]);

        return true;
    }

    /**
     * Register device for user
     */
    private function registerDevice(User $user, array $credentials): ?UserDevice
    {
        if (empty($credentials['fingerprint'])) {
            return null;
        }

        $existingDevice = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $credentials['fingerprint'])
            ->first();

        $isNewDevice = $existingDevice === null;

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'fingerprint' => $credentials['fingerprint'],
            ],
            [
                'device_name' => $credentials['device_name'] ?? 'Unknown Device',
                'device_type' => $credentials['device_type'] ?? 'desktop',
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'last_used_at' => CarbonImmutable::now(),
                'is_revoked' => false,
                'location_country' => $credentials['location_country'] ?? null,
                'location_city' => $credentials['location_city'] ?? null,
            ]
        );

        // Dispatch NewDeviceLogin event if this is a new device
        if ($isNewDevice && $device !== null) {
            $this->eventDispatcher->dispatch(new NewDeviceLogin(
                user: $user,
                device: $device,
                ipAddress: request()->ip()
            ));
        }

        return $device;
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $this->hash->make($newPassword),
            'password_changed_at' => CarbonImmutable::now(),
        ]);

        // Dispatch PasswordChanged event to trigger cooldown
        $this->eventDispatcher->dispatch(new PasswordChanged(
            user: $user,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        ));

        $this->log->channel('audit')->info('Password changed', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Enable/disable 2FA
     */
    public function changeTwoFactor(User $user, bool $enabled): void
    {
        $action = $enabled ? 'enabled' : 'disabled';
        
        $user->update([
            'two_factor_enabled' => $enabled,
        ]);

        // Dispatch TwoFactorChanged event to trigger cooldown
        $this->eventDispatcher->dispatch(new TwoFactorChanged(
            user: $user,
            action: $action,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        ));

        $this->log->channel('audit')->info('2FA changed', [
            'user_id' => $user->id,
            'action' => $action,
            'ip' => request()->ip(),
        ]);
    }
}
