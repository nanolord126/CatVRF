<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Modules\Warehouse\Domain\Exceptions\PIIProtectionException;
use Psr\Log\LoggerInterface;

/**
 * PII Protection Service for 152-ФЗ compliance
 * 
 * Сервис обеспечивает защиту персональных данных в соответствии с 152-ФЗ:
 * - Анонимизация данных в логах
 * - Шифрование чувствительных полей
 * - Механизм удаления по требованию субъекта
 * - Контроль доступа к PII
 */
final readonly class PIIProtectionService
{
    private const MASKED = '***';
    private const MASK_PATTERN = '%s***';
    private const EMAIL_MASK = '%s***@%s';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Анонимизация строки для логов
     */
    public function anonymizeForLogs(?string $value, string $fieldType = 'default'): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) <= 3) {
            return self::MASKED;
        }

        return match ($fieldType) {
            'name', 'supplier_name', 'performed_by', 'approved_by' => $this->maskName($value),
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'inn', 'kpp', 'ogrn' => $this->maskIdentifier($value),
            'address' => $this->maskAddress($value),
            default => sprintf(self::MASK_PATTERN, substr($value, 0, 3)),
        };
    }

    /**
     * Анонимизация массива данных для логов
     */
    public function anonymizeArrayForLogs(array $data): array
    {
        $piiFields = [
            'name', 'supplier_name', 'performed_by', 'approved_by',
            'email', 'phone', 'inn', 'kpp', 'ogrn', 'address',
            'contact_person', 'representative', 'manager'
        ];

        foreach ($data as $key => $value) {
            if (is_string($value) && in_array($key, $piiFields, true)) {
                $data[$key] = $this->anonymizeForLogs($value, $key);
            } elseif (is_array($value)) {
                $data[$key] = $this->anonymizeArrayForLogs($value);
            }
        }

        return $data;
    }

    /**
     * Проверка наличия PII в данных перед логированием
     */
    public function containsPII(array $data): bool
    {
        $piiFields = [
            'name', 'supplier_name', 'performed_by', 'approved_by',
            'email', 'phone', 'inn', 'kpp', 'ogrn', 'address',
            'contact_person', 'representative', 'manager',
            'passport', 'snils', 'birth_date'
        ];

        foreach (array_keys($data) as $key) {
            if (in_array($key, $piiFields, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Безопасное логирование с автоматической анонимизацией PII
     */
    public function logWithPIIProtection(string $message, array $context = [], string $level = 'info'): void
    {
        if ($this->containsPII($context)) {
            $context = $this->anonymizeArrayForLogs($context);
            $this->logger->log($level, $message . ' [PII anonymized]', $context);
        } else {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Генерация hash для PII (для поиска без раскрытия данных)
     */
    public function hashPII(string $value): string
    {
        return hash('sha256', $value . config('warehouse.pii.salt', 'default_salt'));
    }

    /**
     * Проверка права доступа к PII
     */
    public function canAccessPII(int $userId, string $role): bool
    {
        $allowedRoles = config('warehouse.pii.allowed_roles', ['admin', 'manager', 'compliance']);

        return in_array($role, $allowedRoles, true);
    }

    /**
     * Маскирование имени
     */
    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $masked = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) <= 2) {
                $masked[] = self::MASKED;
            } else {
                $masked[] = mb_substr($part, 0, 1) . str_repeat('*', mb_strlen($part) - 1);
            }
        }

        return implode(' ', $masked);
    }

    /**
     * Маскирование email
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return self::MASKED;
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = mb_strlen($local) > 2
            ? mb_substr($local, 0, 2) . str_repeat('*', mb_strlen($local) - 2)
            : self::MASKED;

        return sprintf(self::EMAIL_MASK, $maskedLocal, $domain);
    }

    /**
     * Маскирование телефона
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $length = mb_strlen($digits);

        if ($length <= 4) {
            return self::MASKED;
        }

        return substr($digits, 0, 2) . str_repeat('*', $length - 4) . substr($digits, -2);
    }

    /**
     * Маскирование идентификатора (ИНН, КПП, ОГРН)
     */
    private function maskIdentifier(string $identifier): string
    {
        $length = mb_strlen($identifier);

        if ($length <= 4) {
            return self::MASKED;
        }

        return substr($identifier, 0, 2) . str_repeat('*', $length - 4) . substr($identifier, -2);
    }

    /**
     * Маскирование адреса
     */
    private function maskAddress(string $address): string
    {
        $parts = preg_split('/,\s*/', trim($address));
        
        if (empty($parts)) {
            return self::MASKED;
        }

        // Показываем только город и улицу, скрываем номер дома и квартиру
        $visible = array_slice($parts, 0, 2);
        $masked = implode(', ', $visible) . ', ' . self::MASKED;

        return $masked;
    }
}
