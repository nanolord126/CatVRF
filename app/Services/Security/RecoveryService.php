<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Models\AccountRecoveryLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WebauthnCredential;
use App\Services\Fraud\FraudControlService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Hashing\HashManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use App\Events\Security\RecoveryInitiated;

final class RecoveryService
{
    private const OTP_TTL = 600; // 10 minutes
    private const COOLDOWN_HOURS = 24;
    private const OTP_MIN = 100000; // Minimum OTP value
    private const OTP_MAX = 999999; // Maximum OTP value

    private const BACKUP_CODES_COUNT = 10;

    private const BACKUP_CODE_LENGTH = 8;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControlService,
        private readonly AccountProtectionService $accountProtectionService,
        private readonly DeepfakeDetectionService $deepfakeDetectionService,
        private readonly AuditService $auditService,
        private readonly Repository $cache,
        private readonly DatabaseManager $db,
        private readonly HashManager $hash,
        private readonly LogManager $log,) {}

    /**
     * Initiate account recovery
     */
    public function initRecovery(
        User $user,
        string $method,
        string $ipAddress,
        string $userAgent,
        string $deviceFingerprint
    ): AccountRecoveryLog {
        // Fraud check
        $fraudCheck = $this->fraudControlService->checkRequest([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'ip_address' => $ipAddress,
            'action' => 'recovery_init',
        ]);

        if ($fraudCheck['should_block'] ?? false) {
            throw new \RuntimeException('Recovery blocked due to fraud detection');
        }

        // Check cooldown
        $cooldownKey = "recovery_cooldown:{$user->id}";
        if ($this->cache->has($cooldownKey)) {
            throw new \RuntimeException('Recovery is on cooldown. Please wait 24 hours.');
        }

        // Calculate risk score
        $riskScore = $this->calculateRecoveryRisk($user, $ipAddress, $deviceFingerprint);

        // Create recovery log
        $recoveryLog = AccountRecoveryLog::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'method' => $method,
            'risk_score' => $riskScore,
            'status' => AccountRecoveryLog::STATUS_INITIATED,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_fingerprint' => $deviceFingerprint,
            'initiated_at' => CarbonImmutable::now(),
        ]);

        // High risk -> block and require manual review
        if ($riskScore >= 0.80) {
            $recoveryLog->update([
                'status' => AccountRecoveryLog::STATUS_BLOCKED,
                'failure_reason' => 'High risk score detected',
            ]);

            $this->accountProtectionService->lockAccount($user, 'high_risk_recovery_attempt', [
                'recovery_log_id' => $recoveryLog->id,
                'risk_score' => $riskScore,
            ]);

            throw new \RuntimeException('Recovery blocked due to high risk. Please contact support.');
        }

        // Generate and send OTP based on method
        $this->sendVerificationToken($recoveryLog, $method);

        $this->auditService->logEvent('recovery_initiated', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'method' => $method,
            'risk_score' => $riskScore,
            'recovery_log_id' => $recoveryLog->id,
        ], 'security');

        $this->eventDispatcher->dispatch(new RecoveryInitiated($user, $recoveryLog));

        return $recoveryLog;
    }

    /**
     * Verify recovery step (OTP, backup code, or AI face)
     */
    public function verifyStep(
        AccountRecoveryLog $recoveryLog,
        string $verificationCode,
        ?string $faceImageBase64 = null
    ): bool {
        if ($recoveryLog->status !== AccountRecoveryLog::STATUS_INITIATED) {
            throw new \RuntimeException('Recovery is not in initiated state');
        }

        // Check expiration
        if ($recoveryLog->initiated_at->addMinutes(10)->isPast()) {
            $recoveryLog->update([
                'status' => AccountRecoveryLog::STATUS_FAILED,
                'failure_reason' => 'Recovery expired',
            ]);
            throw new \RuntimeException('Recovery has expired');
        }

        $verified = false;

        switch ($recoveryLog->method) {
            case AccountRecoveryLog::METHOD_EMAIL:
            case AccountRecoveryLog::METHOD_SMS:
                $verified = $this->verifyOTP($recoveryLog, $verificationCode);
                break;

            case AccountRecoveryLog::METHOD_BACKUP_CODE:
                $verified = $this->verifyBackupCode($recoveryLog->user, $verificationCode);
                break;

            case AccountRecoveryLog::METHOD_AI_FACE:
                if (! $faceImageBase64) {
                    throw new \RuntimeException('Face image required for AI verification');
                }
                $verified = $this->verifyFace($recoveryLog->user, $faceImageBase64);
                break;

            default:
                throw new \RuntimeException('Unsupported recovery method');
        }

        if (! $verified) {
            $recoveryLog->update([
                'status' => AccountRecoveryLog::STATUS_FAILED,
                'failure_reason' => 'Verification failed',
            ]);

            // Apply cooldown
            $this->cache->put("recovery_cooldown:{$recoveryLog->user_id}", true, self::COOLDOWN_HOURS * 3600);

            return false;
        }

        $recoveryLog->update([
            'status' => AccountRecoveryLog::STATUS_VERIFIED,
            'verified_at' => CarbonImmutable::now(),
        ]);

        return true;
    }

    /**
     * Complete recovery and create new passkey
     */
    public function completeRecoveryWithNewPasskey(
        AccountRecoveryLog $recoveryLog,
        string $credentialId,
        string $credentialPublicKey,
        int $counter,
        array $transports = []
    ): void {
        if ($recoveryLog->status !== AccountRecoveryLog::STATUS_VERIFIED) {
            throw new \RuntimeException('Recovery must be verified first');
        }

        $this->db->transaction(function () use ($recoveryLog, $credentialId, $credentialPublicKey, $counter, $transports) {
            $user = $recoveryLog->user;

            // Revoke all old credentials
            WebauthnCredential::where('user_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->update([
                    'is_compromised' => true,
                    'compromised_at' => CarbonImmutable::now(),
                ]);

            // Create new credential
            WebauthnCredential::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'credential_id' => $credentialId,
                'credential_public_key' => $credentialPublicKey,
                'counter' => $counter,
                'transports' => $transports,
                'backup_codes' => $this->generateBackupCodes(),
                'backup_codes_remaining' => self::BACKUP_CODES_COUNT,
                'recovery_enabled' => true,
            ]);

            // Logout all sessions
            $user->tokens()->delete();

            // Update recovery log
            $recoveryLog->update([
                'status' => AccountRecoveryLog::STATUS_COMPLETED,
                'completed_at' => CarbonImmutable::now(),
            ]);

            // Reset cooldown
            $this->cache->forget("recovery_cooldown:{$user->id}");

            // Log event
            $this->auditService->logEvent('recovery_completed', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'method' => $recoveryLog->method,
                'duration_seconds' => $recoveryLog->getDurationInSeconds(),
            ], 'security');

            $this->log->$this->logger->info('Account recovery completed', [
                'user_id' => $user->id,
                'method' => $recoveryLog->method,
            ]);
        });
    }

    /**
     * Regenerate backup codes for a credential
     */
    public function regenerateBackupCodes(WebauthnCredential $credential): array
    {
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $code = Str::upper(Str::random(self::BACKUP_CODE_LENGTH));
            $plainCodes[] = $code;
            $hashedCodes[] = $this->hash->make($code);
        }

        $credential->update([
            'backup_codes' => $hashedCodes,
            'backup_codes_remaining' => self::BACKUP_CODES_COUNT,
        ]);

        $this->auditService->logEvent('backup_codes_regenerated', [
            'user_id' => $credential->user_id,
            'tenant_id' => $credential->tenant_id,
            'credential_id' => $credential->id,
        ], 'security');

        return $plainCodes;
    }

    /**
     * Calculate recovery risk score
     */
    private function calculateRecoveryRisk(User $user, string $ipAddress, string $deviceFingerprint): float
    {
        $riskScore = 0.0;

        // New device adds risk
        $device = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $deviceFingerprint)
            ->first();

        if (! $device) {
            $riskScore += 0.30;
        } elseif (! $device->is_trusted) {
            $riskScore += 0.20;
        }

        // Suspicious IP adds risk
        $ipRisk = $this->fraudControlService->checkIpReputation($ipAddress);
        if ($ipRisk['is_suspicious'] ?? false) {
            $riskScore += 0.35;
        }

        // Recent failed attempts add risk
        $recentFailures = AccountRecoveryLog::where('user_id', $user->id)
            ->where('status', AccountRecoveryLog::STATUS_FAILED)
            ->where('initiated_at', '>=', CarbonImmutable::now()->subHours(24))
            ->count();

        if ($recentFailures >= 3) {
            $riskScore += 0.25;
        }

        return min(1.0, $riskScore);
    }

    /**
     * Send verification token (OTP)
     */
    private function sendVerificationToken(AccountRecoveryLog $recoveryLog, string $method): void
    {
        $otp = $this->generateOTP();
        $key = "recovery_otp:{$recoveryLog->id}";

        $this->cache->put($key, $this->hash->make($otp), self::OTP_TTL);

        // In production, send via email/SMS based on method
        // For now, log it
        $this->log->$this->logger->info('Recovery OTP generated', [
            'recovery_log_id' => $recoveryLog->id,
            'method' => $method,
            'otp' => $otp, // REMOVE IN PRODUCTION
        ]);

        // Интеграция с NotificationChannelService
        // $this->notificationManager->route($method, $user->email)->notify(new RecoveryOtpNotification($otp));
    }

    /**
     * Verify OTP
     */
    private function verifyOTP(AccountRecoveryLog $recoveryLog, string $code): bool
    {
        $key = "recovery_otp:{$recoveryLog->id}";
        $hashedOtp = $this->cache->get($key);

        if (! $hashedOtp) {
            return false;
        }

        $verified = $this->hash->check($code, $hashedOtp);

        if ($verified) {
            $this->cache->forget($key);
        }

        return $verified;
    }

    /**
     * Verify backup code
     */
    private function verifyBackupCode(User $user, string $code): bool
    {
        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->where('backup_codes_remaining', '>', 0)
            ->first();

        if (! $credential) {
            return false;
        }

        $backupCodes = $credential->backup_codes ?? [];

        foreach ($backupCodes as $index => $hashedCode) {
            if ($this->hash->check($code, $hashedCode)) {
                // Remove used code
                unset($backupCodes[$index]);
                $credential->update([
                    'backup_codes' => array_values($backupCodes),
                    'backup_codes_remaining' => max(0, $credential->backup_codes_remaining - 1),
                    'last_backup_code_used_at' => CarbonImmutable::now(),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Verify face using AI deepfake detection
     */
    private function verifyFace(User $user, string $faceImageBase64): bool
    {
        $result = $this->deepfakeDetectionService->verifyFace(
            $user,
            $faceImageBase64
        );

        return $result['is_verified'] ?? false;
    }

    /**
     * Generate OTP
     */
    private function generateOTP(): string
    {
        return (string) random_int(self::OTP_MIN, self::OTP_MAX);
    }

    /**
     * Generate backup codes
     */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $code = Str::upper(Str::random(self::BACKUP_CODE_LENGTH));
            $codes[] = $this->hash->make($code);
        }

        return $codes;
    }
}
