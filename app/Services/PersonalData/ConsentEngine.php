<?php

declare(strict_types=1);

namespace App\Services\PersonalData;

use App\Enums\ConsentStatus;
use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

/**
 * Consent Engine for 152-FZ Compliance
 * 
 * Manages granular consent for personal data processing.
 * Enforces consent requirements before processing biometric and behavioral data.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: All biometric/behavioral data processing MUST check consent first.
 */
final readonly class ConsentEngine
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PersonalDataAccessAudit $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
    ) {}

    /**
     * Check if user has active consent for a specific type
     */
    public function hasConsent(User $user, ConsentType $type): bool
    {
        $consent = $this->getActiveConsent($user, $type);

        return $consent !== null && $consent->isActive();
    }

    /**
     * Get active consent for a specific type
     */
    public function getActiveConsent(User $user, ConsentType $type): ?UserConsent
    {
        return UserConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', $type)
            ->active()
            ->orderBy('granted_at', 'desc')
            ->first();
    }

    /**
     * Require consent or throw exception
     * 
     * @throws \RuntimeException If consent is not granted
     */
    public function requireConsent(User $user, ConsentType $type, string $context = 'general'): void
    {
        if (! $this->hasConsent($user, $type)) {
            $this->logger->warning('Consent required but not granted', [
                'user_id' => $user->id,
                'consent_type' => $type->value,
                'context' => $context,
            ]);

            throw new \RuntimeException(
                sprintf(
                    'Consent for %s is required but not granted. User must provide %s consent.',
                    $type->label(),
                    $type->requiresEnhancedForm() ? 'written/UKEDS' : 'explicit'
                ),
                403
            );
        }
    }

    /**
     * Grant consent for a specific type
     */
    public function grantConsent(
        User $user,
        ConsentType $type,
        Request $request,
        ?string $consentText = null,
        ?array $purposes = null
    ): UserConsent {
        // Check if biometric consent requires enhanced form
        if ($type->requiresEnhancedForm()) {
            $signatureMethod = $request->input('signature_method', 'click');
            
            if (! in_array($signatureMethod, ['ukep', 'written', 'click'], true)) {
                throw new \RuntimeException(
                    'Biometric consent requires enhanced form (UKEDS or written signature)',
                    400
                );
            }
        } else {
            $signatureMethod = 'click';
        }

        $consent = $this->db->transaction(function () use ($user, $type, $request, $consentText, $purposes, $signatureMethod) {
            $consent = UserConsent::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'consent_type' => $type,
                'status' => ConsentStatus::GRANTED,
                'consent_text' => $consentText ?? $this->getDefaultConsentText($type),
                'consent_version' => '1.0',
                'consent_purposes' => $purposes ?? $this->getDefaultPurposes($type),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => $request->input('device_fingerprint'),
                'signature_method' => $signatureMethod,
                'granted_at' => CarbonImmutable::now(),
                'expires_at' => $type === ConsentType::LONG_TERM_STORAGE 
                    ? CarbonImmutable::now()->addYears(7) 
                    : null,
                'metadata' => [
                    'context' => $request->input('context', 'registration'),
                    'source' => $request->input('source', 'web'),
                ],
            ]);

            // Update user model with consent reference if biometric
            if ($type->isBiometric()) {
                $this->updateUserConsentReference($user, $type, $consent->id);
            }

            // Audit log
            $this->audit->logConsentGranted($user, $type, $request);

            $this->logger->info('Consent granted', [
                'user_id' => $user->id,
                'consent_type' => $type->value,
                'consent_id' => $consent->id,
                'signature_method' => $signatureMethod,
            ]);

            return $consent;
        });

        return $consent;
    }

    /**
     * Withdraw consent for a specific type
     */
    public function withdrawConsent(
        User $user,
        ConsentType $type,
        ?string $reason = null,
        ?int $withdrawnBy = null
    ): UserConsent {
        $activeConsent = $this->getActiveConsent($user, $type);

        if ($activeConsent === null) {
            throw new \RuntimeException('No active consent found for withdrawal', 404);
        }

        $activeConsent->withdraw($reason, $withdrawnBy ?? $user->id);

        $this->logger->warning('Consent withdrawn', [
            'user_id' => $user->id,
            'consent_type' => $type->value,
            'consent_id' => $activeConsent->id,
            'reason' => $reason,
        ]);

        return $activeConsent;
    }

    /**
     * Batch grant multiple consents
     */
    public function grantBatchConsents(
        User $user,
        array $consentTypes,
        Request $request
    ): array {
        $granted = [];

        foreach ($consentTypes as $type) {
            if (! $type instanceof ConsentType) {
                $type = ConsentType::from($type);
            }

            try {
                $consent = $this->grantConsent($user, $type, $request);
                $granted[$type->value] = $consent;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to grant consent in batch', [
                    'user_id' => $user->id,
                    'consent_type' => $type->value,
                    'error' => $e->getMessage(),
                ]);
                $granted[$type->value] = null;
            }
        }

        return $granted;
    }

    /**
     * Get all active consents for user
     */
    public function getUserConsents(User $user): array
    {
        $consents = UserConsent::query()
            ->where('user_id', $user->id)
            ->active()
            ->get()
            ->keyBy('consent_type');

        return [
            'registration' => $consents->get(ConsentType::REGISTRATION->value)?->isActive() ?? false,
            'orders' => $consents->get(ConsentType::ORDERS->value)?->isActive() ?? false,
            'communications' => $consents->get(ConsentType::COMMUNICATIONS->value)?->isActive() ?? false,
            'biometric_face_id' => $consents->get(ConsentType::BIOMETRIC_FACE_ID->value)?->isActive() ?? false,
            'biometric_liveness' => $consents->get(ConsentType::BIOMETRIC_LIVENESS->value)?->isActive() ?? false,
            'biometric_behavioral' => $consents->get(ConsentType::BIOMETRIC_BEHAVIORAL->value)?->isActive() ?? false,
            'biometric_voice' => $consents->get(ConsentType::BIOMETRIC_VOICE->value)?->isActive() ?? false,
            'kyb_verification' => $consents->get(ConsentType::KYB_VERIFICATION->value)?->isActive() ?? false,
            'document_verification' => $consents->get(ConsentType::DOCUMENT_VERIFICATION->value)?->isActive() ?? false,
            'long_term_storage' => $consents->get(ConsentType::LONG_TERM_STORAGE->value)?->isActive() ?? false,
        ];
    }

    /**
     * Check if user has any biometric consent
     */
    public function hasBiometricConsent(User $user): bool
    {
        return UserConsent::query()
            ->where('user_id', $user->id)
            ->biometric()
            ->active()
            ->exists();
    }

    /**
     * Get default consent text for a type
     */
    private function getDefaultConsentText(ConsentType $type): string
    {
        $operator = config('personal-data.operator_name', 'CatVRF LLC');
        $address = config('personal-data.operator_address', 'Russia, Moscow');
        $contact = config('personal-data.operator_contact', 'privacy@catvrf.ru');

        return match ($type) {
            ConsentType::REGISTRATION => sprintf(
                "Я, являясь субъектом персональных данных, даю согласие оператору %s (%s, контакт: %s) на обработку моих персональных данных (ФИО, email, телефон) для целей регистрации и использования аккаунта на платформе CatVRF. Согласие предоставляется на срок не более %d дней. Я проинформирован о правах на доступ, уточнение, блокировку и уничтожение персональных данных.",
                $operator,
                $address,
                $contact,
                $type->retentionDays()
            ),
            ConsentType::BIOMETRIC_FACE_ID => sprintf(
                "Я, являясь субъектом персональных данных, даю письменное согласие оператору %s (%s, контакт: %s) на обработку моих биометрических персональных данных (изображение лица, Face ID) для целей аутентификации и верификации личности на платформе CatVRF. Согласие предоставляется на срок не более %d дней. Я проинформирован, что могу отозвать данное согласие в любой момент, при этом мои биометрические данные будут уничтожены в течение 30 дней.",
                $operator,
                $address,
                $contact,
                $type->retentionDays()
            ),
            ConsentType::BIOMETRIC_BEHAVIORAL => sprintf(
                "Я, являясь субъектом персональных данных, даю письменное согласие оператору %s (%s, контакт: %s) на обработку моих биометрических персональных данных (поведенческие паттерны: клавиатурный почерк, движения мыши, жесты) для целей непрерывной аутентификации и защиты от несанкционированного доступа на платформе CatVRF. Согласие предоставляется на срок не более %d дней. Я проинформирован, что могу отозвать данное согласие в любой момент.",
                $operator,
                $address,
                $contact,
                $type->retentionDays()
            ),
            default => sprintf(
                "Я даю согласие оператору %s на обработку персональных данных для целей %s. Согласие предоставляется на срок не более %d дней.",
                $operator,
                $type->label(),
                $type->retentionDays()
            ),
        };
    }

    /**
     * Get default purposes for a consent type
     */
    private function getDefaultPurposes(ConsentType $type): array
    {
        return match ($type) {
            ConsentType::REGISTRATION => ['account_creation', 'authentication', 'profile_management'],
            ConsentType::ORDERS => ['order_processing', 'payment_processing', 'delivery'],
            ConsentType::COMMUNICATIONS => ['notifications', 'promotional_emails', 'sms_alerts'],
            ConsentType::BIOMETRIC_FACE_ID => ['authentication', 'identity_verification', 'fraud_prevention'],
            ConsentType::BIOMETRIC_LIVENESS => ['liveness_check', 'anti_spoofing'],
            ConsentType::BIOMETRIC_BEHAVIORAL => ['continuous_authentication', 'anomaly_detection', 'security_monitoring'],
            ConsentType::BIOMETRIC_VOICE => ['voice_authentication', 'identity_verification'],
            ConsentType::KYB_VERIFICATION => ['business_verification', 'compliance_checking'],
            ConsentType::DOCUMENT_VERIFICATION => ['document_validation', 'identity_confirmation'],
            ConsentType::LONG_TERM_STORAGE => ['analytics', 'reporting', 'retention'],
        };
    }

    /**
     * Update user model with consent reference
     */
    private function updateUserConsentReference(User $user, ConsentType $type, int $consentId): void
    {
        $field = match ($type) {
            ConsentType::BIOMETRIC_FACE_ID, ConsentType::BIOMETRIC_LIVENESS => 'face_id_consent_id',
            ConsentType::BIOMETRIC_BEHAVIORAL => 'behavioral_consent_id',
            default => null,
        };

        if ($field !== null) {
            $user->update([$field => $consentId]);
        }
    }

    /**
     * Check and expire consents that are past their expiration date
     */
    public function expireConsents(): int
    {
        $expiredCount = UserConsent::query()
            ->where('status', ConsentStatus::GRANTED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', CarbonImmutable::now())
            ->update(['status' => ConsentStatus::EXPIRED]);

        if ($expiredCount > 0) {
            $this->logger->info('Consents expired', ['count' => $expiredCount]);
        }

        return $expiredCount;
    }
}
