<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Modules\Warehouse\Domain\Exceptions\ConsentException;

/**
 * Consent Management Service for 152-ФЗ compliance
 * 
 * Сервис обеспечивает управление согласиями на обработку персональных данных:
 * - Запись согласий пользователей
 * - Отзыв согласий
 * - Проверка наличия действующего согласия
 * - Логирование изменений согласий
 */
final readonly class ConsentManagementService
{
    private const CONSENT_TYPES = [
        'personal_data_processing' => 'Обработка персональных данных',
        'data_sharing' => 'Передача данных третьим лицам',
        'marketing_communications' => 'Маркетинговые коммуникации',
        'analytics' => 'Аналитика и статистика',
        'biometric_data' => 'Биометрические данные',
    ];

    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    /**
     * Создание записи о согласии
     */
    public function createConsent(
        int $userId,
        string $consentType,
        string $consentText,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): string {
        $this->validateConsentType($consentType);

        $consentId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('pii_consents')->insert([
            'id' => $consentId,
            'user_id' => $userId,
            'consent_type' => $consentType,
            'consent_text' => $consentText,
            'granted_at' => now(),
            'expires_at' => null, // Согласие бессрочное
            'revoked_at' => null,
            'revocation_reason' => null,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'tenant_id' => auth()->user()?->tenant_id,
            'created_at' => now(),
        ]);

        return $consentId;
    }

    /**
     * Отзыв согласия
     */
    public function revokeConsent(
        string $consentId,
        string $reason,
        int $revokedBy
    ): void {
        $affected = $this->db->table('pii_consents')
            ->where('id', $consentId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'revoked_by' => $revokedBy,
            ]);

        if ($affected === 0) {
            throw ConsentException::notFoundOrAlreadyRevoked($consentId);
        }
    }

    /**
     * Проверка наличия действующего согласия
     */
    public function hasActiveConsent(int $userId, string $consentType): bool
    {
        $this->validateConsentType($consentType);

        return $this->db->table('pii_consents')
            ->where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Получение всех активных согласий пользователя
     */
    public function getActiveConsents(int $userId): array
    {
        return $this->db->table('pii_consents')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('granted_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Получение истории согласий пользователя
     */
    public function getConsentHistory(int $userId): array
    {
        return $this->db->table('pii_consents')
            ->where('user_id', $userId)
            ->orderBy('granted_at', 'desc')
            ->orderBy('revoked_at')
            ->get()
            ->toArray();
    }

    /**
     * Создание пакетного согласия (несколько типов сразу)
     */
    public function createBulkConsent(
        int $userId,
        array $consentTypes,
        string $consentText,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $consentIds = [];

        foreach ($consentTypes as $consentType) {
            $consentIds[] = $this->createConsent(
                $userId,
                $consentType,
                $consentText,
                $ipAddress,
                $userAgent
            );
        }

        return $consentIds;
    }

    /**
     * Отзыв всех согласий пользователя
     */
    public function revokeAllConsents(int $userId, string $reason, int $revokedBy): int
    {
        return $this->db->table('pii_consents')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'revoked_by' => $revokedBy,
            ]);
    }

    /**
     * Проверка обязательных согласий для обработки данных
     */
    public function checkRequiredConsents(int $userId, array $requiredConsents): array
    {
        $missingConsents = [];

        foreach ($requiredConsents as $consentType) {
            if (!$this->hasActiveConsent($userId, $consentType)) {
                $missingConsents[] = [
                    'type' => $consentType,
                    'description' => self::CONSENT_TYPES[$consentType] ?? $consentType,
                ];
            }
        }

        return $missingConsents;
    }

    /**
     * Валидация типа согласия
     */
    private function validateConsentType(string $consentType): void
    {
        if (!array_key_exists($consentType, self::CONSENT_TYPES)) {
            throw ConsentException::invalidConsentType($consentType, array_keys(self::CONSENT_TYPES));
        }
    }

    /**
     * Получение описания типа согласия
     */
    public static function getConsentTypeDescription(string $consentType): string
    {
        return self::CONSENT_TYPES[$consentType] ?? $consentType;
    }

    /**
     * Получение всех доступных типов согласий
     */
    public static function getAvailableConsentTypes(): array
    {
        return self::CONSENT_TYPES;
    }

    /**
     * Проверка срока действия согласия
     */
    public function isConsentExpired(string $consentId): bool
    {
        $consent = $this->db->table('pii_consents')
            ->where('id', $consentId)
            ->first();

        if (!$consent) {
            return true;
        }

        if ($consent->revoked_at !== null) {
            return true;
        }

        if ($consent->expires_at === null) {
            return false;
        }

        return Carbon::parse($consent->expires_at)->isPast();
    }

    /**
     * Автоматическое продление согласия (если применимо)
     */
    public function renewConsent(string $consentId, ?\DateTimeImmutable $newExpiryDate = null): void
    {
        $this->db->table('pii_consents')
            ->where('id', $consentId)
            ->update([
                'expires_at' => $newExpiryDate ? $newExpiryDate->format('Y-m-d H:i:s') : null,
                'updated_at' => now(),
            ]);
    }
}
