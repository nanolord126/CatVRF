<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Collection;

use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Notifications\Auth\TwoFactorCodeNotification;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class TwoFactorService
{
    use WithAuditLogging;

    private readonly Google2FA $google2fa;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly AuditService $audit,
    ) {
        $this->google2fa = new Google2FA();
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Store secret temporarily until confirmed
        $this->cache->put("2fa_secret:{$user->id}", $secret, CarbonImmutable::now()->addMinutes(10));

        $this->log->channel('audit')->$this->logger->info('2FA setup initiated', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm(User $user, string $code): bool
    {
        $secret = $this->cache->get("2fa_secret:{$user->id}");

        if (! $secret) {
            throw ValidationException::withMessages(['code' => ['Setup session expired']]);
        }

        if (! $this->google2fa->verifyKey($secret, $code)) {
            throw ValidationException::withMessages(['code' => ['Invalid verification code']]);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => CarbonImmutable::now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        $this->cache->forget("2fa_secret:{$user->id}");

        $this->log->channel('audit')->$this->logger->info('2FA enabled', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return true;
    }

    /**
     * Verify 2FA code
     */
    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);

        // Check TOTP code
        if ($this->google2fa->verifyKey($secret, $code, config('auth.2fa.window', 2))) {
            return true;
        }

        // Check recovery code
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (in_array($code, $recoveryCodes, true)) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));

            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            ]);

            $this->log->channel('audit')->$this->logger->info('2FA recovery code used', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        }

        throw ValidationException::withMessages(['code' => ['Invalid verification code']]);
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user, string $code): bool
    {
        $this->verify($user, $code);

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $this->log->channel('audit')->$this->logger->info('2FA disabled', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return true;
    }

    /**
     * Send 2FA code via email (fallback)
     */
    public function sendEmailCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->cache->put("2fa_email_code:{$user->id}", $code, CarbonImmutable::now()->addMinutes(5));

        $user->notify(new TwoFactorCodeNotification($code));

        $this->log->channel('audit')->$this->logger->info('2FA email code sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $code;
    }

    /**
     * Verify email code
     */
    public function verifyEmailCode(User $user, string $code): bool
    {
        $expectedCode = $this->cache->get("2fa_email_code:{$user->id}");

        if (! $expectedCode || $expectedCode !== $code) {
            throw ValidationException::withMessages(['code' => ['Invalid email verification code']]);
        }

        $this->cache->forget("2fa_email_code:{$user->id}");

        return true;
    }

    /**
     * Generate recovery codes
     */
    private function generateRecoveryCodes(): array
    {
        return new Collection(range(1, 8))
            ->map(fn () => Str::random(10))
            ->toArray();
    }
}
