<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Encryption\Encrypter;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * PII Protection Service
 *
 * Handles Personally Identifiable Information (PII) protection:
 * - Anonymization in logs (152-ФЗ compliance)
 * - Encryption in database
 * - Deletion mechanisms (right to be forgotten)
 * - Data masking for display
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class PIIProtectionService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache,
        private readonly Encrypter $encrypter
    ) {}

    /**
     * Anonymize data for logging
     *
     * @param  array  $data  Data to anonymize
     * @param  array  $piiFields  Fields containing PII
     * @return array Anonymized data
     */
    public function anonymizeForLog(array $data, array $piiFields = []): array
    {
        $defaultPiiFields = [
            'name', 'first_name', 'last_name', 'middle_name',
            'email', 'phone', 'mobile', 'address',
            'passport', 'inn', 'snils', 'birth_date',
            'medical_record', 'diagnosis', 'symptoms',
        ];

        $fieldsToAnonymize = array_merge($defaultPiiFields, $piiFields);

        foreach ($data as $key => $value) {
            if (in_array($key, $fieldsToAnonymize, true)) {
                $data[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->anonymizeForLog($value, $piiFields);
            }
        }

        return $data;
    }

    /**
     * Encrypt PII for database storage
     *
     * @param  string  $value  Value to encrypt
     * @return string Encrypted value
     */
    public function encrypt(string $value): string
    {
        return $this->encrypter->encryptString($value);
    }

    /**
     * Decrypt PII from database
     *
     * @param  string  $encryptedValue  Encrypted value
     * @return string Decrypted value
     */
    public function decrypt(string $encryptedValue): string
    {
        try {
            return $this->encrypter->decryptString($encryptedValue);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt PII', [
                'error' => $e->getMessage(),
            ]);
            return '[DECRYPTION_ERROR]';
        }
    }

    /**
     * Delete user data (right to be forgotten - 152-ФЗ)
     *
     * @param  int  $userId  User ID
     * @param  string  $reason  Deletion reason
     * @param  int  $adminUserId  Admin performing deletion
     * @return bool
     */
    public function deleteUserData(int $userId, string $reason, int $adminUserId): bool
    {
        return $this->db->transaction(function () use ($userId, $reason, $adminUserId) {
            $user = $this->db->table('users')
                ->where('id', $userId)
                ->first();

            if (! $user) {
                throw new \RuntimeException("User not found: {$userId}");
            }

            $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'name' => 'DELETED',
                    'email' => "deleted_{$userId}@deleted.local",
                    'phone' => null,
                    'address' => null,
                    'passport' => null,
                    'inn' => null,
                    'snils' => null,
                    'birth_date' => null,
                    'deleted_at' => now(),
                    'deletion_reason' => $reason,
                ]);

            $this->db->table('audit_logs')
                ->where('user_id', $userId)
                ->update([
                    'context' => $this->anonymizeForLog(
                        json_decode('{"context": "placeholder"}', true)
                    ),
                ]);

            $this->logAction(
                action: 'user_data_deleted',
                entityType: 'User',
                entityId: $userId,
                context: [
                    'reason' => $reason,
                    'admin_user_id' => $adminUserId,
                ],
                userId: $adminUserId,
                tenantId: $user->tenant_id ?? 0
            );

            return true;
        });
    }

    /**
     * Mask value for display
     *
     * @param  mixed  $value  Value to mask
     * @return string Masked value
     */
    public function maskValue(mixed $value): string
    {
        if (is_null($value)) {
            return '[NULL]';
        }

        $stringValue = (string) $value;
        $length = strlen($stringValue);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visibleChars = 2;
        $maskedChars = $length - ($visibleChars * 2);

        return substr($stringValue, 0, $visibleChars)
            . str_repeat('*', $maskedChars)
            . substr($stringValue, -$visibleChars);
    }

    /**
     * Mask email
     *
     * @param  string  $email  Email to mask
     * @return string Masked email
     */
    public function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $this->maskValue($email);
        }

        [$local, $domain] = explode('@', $email);

        $maskedLocal = substr($local, 0, 2) . str_repeat('*', strlen($local) - 2);
        $maskedDomain = substr($domain, 0, 1) . str_repeat('*', strlen($domain) - 1);

        return $maskedLocal . '@' . $maskedDomain;
    }

    /**
     * Mask phone number
     *
     * @param  string  $phone  Phone to mask
     * @return string Masked phone
     */
    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($digits);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $countryCode = substr($digits, 0, 1);
        $areaCode = substr($digits, 1, 3);
        $number = substr($digits, 4);

        return $countryCode . str_repeat('*', 3) . str_repeat('*', strlen($number));
    }

    /**
     * Anonymize medical data (ФЗ-323 compliance)
     *
     * @param  array  $medicalData  Medical data
     * @return array Anonymized data
     */
    public function anonymizeMedicalData(array $medicalData): array
    {
        $medicalPiiFields = [
            'patient_name', 'patient_id', 'diagnosis', 'symptoms',
            'medical_history', 'prescriptions', 'doctor_name',
            'hospital_name', 'treatment_details',
        ];

        return $this->anonymizeForLog($medicalData, $medicalPiiFields);
    }

    /**
     * Check if data contains PII
     *
     * @param  array  $data  Data to check
     * @return bool Contains PII
     */
    public function containsPII(array $data): bool
    {
        $piiIndicators = [
            'name', 'email', 'phone', 'address',
            'passport', 'inn', 'snils',
            'diagnosis', 'medical', 'patient',
        ];

        $dataString = json_encode($data, JSON_THROW_ON_ERROR);
        $lowerData = strtolower($dataString);

        foreach ($piiIndicators as $indicator) {
            if (str_contains($lowerData, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log with PII protection
     *
     * @param  string  $message  Log message
     * @param  array  $context  Context data
     * @param  string  $level  Log level
     * @return void
     */
    public function logWithPIIProtection(string $message, array $context, string $level = 'info'): void
    {
        $anonymizedContext = $this->anonymizeForLog($context);

        match ($level) {
            'info' => $this->logger->info($message, $anonymizedContext),
            'warning' => $this->logger->warning($message, $anonymizedContext),
            'error' => $this->logger->error($message, $anonymizedContext),
            'debug' => $this->logger->debug($message, $anonymizedContext),
            default => $this->logger->info($message, $anonymizedContext),
        };
    }

    /**
     * Get data retention policy for entity type
     *
     * @param  string  $entityType  Entity type
     * @return array Retention policy
     */
    public function getRetentionPolicy(string $entityType): array
    {
        return match ($entityType) {
            'user', 'customer' => [
                'active_retention_days' => 365 * 5,
                'anonymized_retention_days' => 365 * 3,
                'deletion_after_days' => 365 * 8,
            ],
            'order', 'transaction' => [
                'active_retention_days' => 365 * 7,
                'anonymized_retention_days' => 365 * 3,
                'deletion_after_days' => 365 * 10,
            ],
            'medical_record' => [
                'active_retention_days' => 365 * 50,
                'anonymized_retention_days' => 365 * 10,
                'deletion_after_days' => 365 * 60,
            ],
            'audit_log' => [
                'active_retention_days' => 365 * 3,
                'anonymized_retention_days' => 365 * 2,
                'deletion_after_days' => 365 * 5,
            ],
            default => [
                'active_retention_days' => 365 * 3,
                'anonymized_retention_days' => 365 * 1,
                'deletion_after_days' => 365 * 4,
            ],
        };
    }

    /**
     * Anonymize old data based on retention policy
     *
     * @param  string  $entityType  Entity type
     * @param  int  $tenantId  Tenant ID
     * @return array Anonymization results
     */
    public function anonymizeOldData(string $entityType, int $tenantId): array
    {
        $policy = $this->getRetentionPolicy($entityType);
        $anonymizationDate = now()->subDays($policy['active_retention_days']);

        $results = [
            'entity_type' => $entityType,
            'tenant_id' => $tenantId,
            'anonymized_count' => 0,
            'errors' => [],
        ];

        match ($entityType) {
            'user' => $results = $this->anonymizeUsers($anonymizationDate, $tenantId),
            'audit_log' => $results = $this->anonymizeAuditLogs($anonymizationDate, $tenantId),
            default => $results['errors'][] = "Anonymization not implemented for: {$entityType}",
        };

        return $results;
    }

    /**
     * Anonymize users
     *
     * @param  \Carbon\Carbon  $beforeDate  Date threshold
     * @param  int  $tenantId  Tenant ID
     * @return array Results
     */
    private function anonymizeUsers(\Carbon\Carbon $beforeDate, int $tenantId): array
    {
        $users = $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<', $beforeDate)
            ->whereNull('deleted_at')
            ->get();

        $anonymizedCount = 0;

        foreach ($users as $user) {
            $this->db->table('users')
                ->where('id', $user->id)
                ->update([
                    'name' => 'ANONYMIZED',
                    'email' => "anon_{$user->id}@anon.local",
                    'phone' => null,
                    'address' => null,
                ]);

            $anonymizedCount++;
        }

        return [
            'entity_type' => 'user',
            'tenant_id' => $tenantId,
            'anonymized_count' => $anonymizedCount,
            'errors' => [],
        ];
    }

    /**
     * Anonymize audit logs
     *
     * @param  \Carbon\Carbon  $beforeDate  Date threshold
     * @param  int  $tenantId  Tenant ID
     * @return array Results
     */
    private function anonymizeAuditLogs(\Carbon\Carbon $beforeDate, int $tenantId): array
    {
        $logs = $this->db->table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<', $beforeDate)
            ->get();

        $anonymizedCount = 0;

        foreach ($logs as $log) {
            $context = json_decode($log->context ?? '{}', true);
            $anonymizedContext = $this->anonymizeForLog($context);

            $this->db->table('audit_logs')
                ->where('id', $log->id)
                ->update([
                    'context' => json_encode($anonymizedContext),
                    'anonymized' => true,
                    'anonymized_at' => now(),
                ]);

            $anonymizedCount++;
        }

        return [
            'entity_type' => 'audit_log',
            'tenant_id' => $tenantId,
            'anonymized_count' => $anonymizedCount,
            'errors' => [],
        ];
    }
}
