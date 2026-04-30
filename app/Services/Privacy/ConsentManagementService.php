<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Models\ConsentRecord;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Log\LogManager;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final readonly class ConsentManagementService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly LogManager $log,) {}

    /**
     * Grant consent for specific purposes
     */
    public function grantConsent(
        int $userId,
        array $consentPurposes,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'consent_grant',
            amount: 0,
            correlationId: $correlationId,
        );

        $user = User::findOrFail($userId);
        $results = [];

        foreach ($consentPurposes as $purpose) {
            $consentRecord = ConsentRecord::updateOrCreate(
                [
                    'user_id' => $userId,
                    'consent_type' => $purpose,
                ],
                [
                    'granted' => true,
                    'granted_at' => CarbonImmutable::now(),
                    'version' => $this->getCurrentConsentVersion($purpose),
                    'expires_at' => $this->calculateExpiry($purpose),
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'correlation_id' => $correlationId,
                ]
            );

            $results[] = [
                'consent_type' => $purpose,
                'granted' => true,
                'granted_at' => $consentRecord->granted_at,
                'expires_at' => $consentRecord->expires_at,
            ];

            // Audit log
            $this->audit->record(
                action: 'consent_granted',
                subjectType: ConsentRecord::class,
                subjectId: $consentRecord->id,
                newValues: [
                    'user_id' => $userId,
                    'consent_type' => $purpose,
                    'version' => $consentRecord->version,
                ],
                correlationId: $correlationId,
            );
        }

        return $results;
    }

    /**
     * Revoke consent for specific purposes
     */
    public function revokeConsent(
        int $userId,
        array $consentPurposes,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'consent_revoke',
            amount: 0,
            correlationId: $correlationId,
        );

        $results = [];

        foreach ($consentPurposes as $purpose) {
            $consentRecord = ConsentRecord::where('user_id', $userId)
                ->where('consent_type', $purpose)
                ->where('granted', true)
                ->first();

            if ($consentRecord) {
                $consentRecord->update([
                    'granted' => false,
                    'revoked_at' => CarbonImmutable::now(),
                    'revocation_reason' => 'user_initiated',
                ]);

                // Trigger data deletion for sensitive consents
                if ($this->requiresDataDeletion($purpose)) {
                    // In production, dispatch data deletion job
                    $this->log->$this->logger->info('Consent revoked, data deletion required', [
                        'user_id' => $userId,
                        'consent_type' => $purpose,
                    ]);
                }

                // Audit log
                $this->audit->record(
                    action: 'consent_revoked',
                    subjectType: ConsentRecord::class,
                    subjectId: $consentRecord->id,
                    newValues: [
                        'user_id' => $userId,
                        'consent_type' => $purpose,
                    ],
                    correlationId: $correlationId,
                );
            }

            $results[] = [
                'consent_type' => $purpose,
                'revoked' => $consentRecord !== null,
            ];
        }

        return $results;
    }

    /**
     * Check if user has granted specific consent
     */
    public function hasConsent(int $userId, string $consentType): bool
    {
        $consent = ConsentRecord::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('granted', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            })
            ->first();

        return $consent !== null;
    }

    /**
     * Check if user has any of the required consents
     */
    public function hasAnyConsent(int $userId, array $consentTypes): bool
    {
        foreach ($consentTypes as $type) {
            if ($this->hasConsent($userId, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all required consents
     */
    public function hasAllConsents(int $userId, array $consentTypes): bool
    {
        foreach ($consentTypes as $type) {
            if (! $this->hasConsent($userId, $type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all user consents
     */
    public function getUserConsents(int $userId): array
    {
        $consents = ConsentRecord::where('user_id', $userId)->get();

        return $consents->map(function ($consent) {
            return [
                'consent_type' => $consent->consent_type,
                'granted' => $consent->granted,
                'granted_at' => $consent->granted_at,
                'revoked_at' => $consent->revoked_at,
                'expires_at' => $consent->expires_at,
                'version' => $consent->version,
                'is_valid' => $consent->granted &&
                    (is_null($consent->expires_at) || $consent->expires_at->isFuture()),
            ];
        })->toArray();
    }

    /**
     * Check for expired consents and notify users
     */
    public function checkExpiredConsents(): int
    {
        $expiredCount = ConsentRecord::where('granted', true)
            ->where('expires_at', '<', CarbonImmutable::now())
            ->update([
                'granted' => false,
                'revoked_at' => CarbonImmutable::now(),
                'revocation_reason' => 'expired',
            ]);

        if ($expiredCount > 0) {
            $this->log->$this->logger->info('Expired consents revoked', ['count' => $expiredCount]);
        }

        return $expiredCount;
    }

    private function getCurrentConsentVersion(string $purpose): string
    {
        // In production, fetch from config or database
        $versions = [
            'biometric' => '1.0',
            'behavioral' => '1.0',
            'location' => '1.0',
            'medical' => '1.0',
            'payment' => '1.0',
            'analytics' => '1.0',
            'marketing' => '1.0',
            'sharing' => '1.0',
            'ai_training' => '1.0',
        ];

        return $versions[$purpose] ?? '1.0';
    }

    private function calculateExpiry(string $purpose): ?Carbon
    {
        // Some consents never expire
        $permanentConsents = ['medical', 'payment'];

        if (in_array($purpose, $permanentConsents, true)) {
            return null;
        }

        // Marketing consent expires after 2 years
        if ($purpose === 'marketing') {
            return CarbonImmutable::now()->addYears(2);
        }

        // Analytics consent expires after 1 year
        if ($purpose === 'analytics') {
            return CarbonImmutable::now()->addYear();
        }

        // Default: no expiry
        return null;
    }

    private function requiresDataDeletion(string $purpose): bool
    {
        // Sensitive consents require data deletion on revocation
        $sensitiveConsents = ['biometric', 'behavioral', 'location', 'ai_training'];

        return in_array($purpose, $sensitiveConsents, true);
    }
}
