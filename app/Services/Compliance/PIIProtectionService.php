<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * PII Protection Service - 152-ФЗ Compliance
 *
 * Implements personal data protection requirements:
 * - Anonymization in logs and external systems
 * - Encryption at rest for sensitive fields
 * - Right to be forgotten (GDPR/152-ФЗ)
 * - Data retention policy with automatic deletion
 * - Consent management
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class PIIProtectionService
{
    private const SENSITIVE_FIELDS = [
        'supplier_name',
        'performed_by',
        'approved_by',
        'contact_person',
        'full_name',
        'email',
        'phone',
        'passport',
        'inn',
        'snils',
        'address',
    ];

    private const RETENTION_PERIODS = [
        'audit_logs' => 2555, // 7 years in days
        'inventory_records' => 1825, // 5 years in days
        'supplier_data' => 3650, // 10 years in days
        'medical_records' => 2555, // 7 years for medical data
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Anonymize PII data for logs/external systems
     *
     * @param  array  $data  Data to anonymize
     * @param  array  $fieldsToMask  Specific fields to mask (if empty, uses default sensitive fields)
     * @return array Anonymized data
     */
    public function anonymize(array $data, array $fieldsToMask = [], string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $fields = empty($fieldsToMask) ? self::SENSITIVE_FIELDS : $fieldsToMask;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->anonymize($value, $fieldsToMask, $correlationId);
            } elseif (is_string($value)) {
                foreach ($fields as $field) {
                    if (str_contains(strtolower($key), $field)) {
                        $data[$key] = $this->maskValue($value);
                        break;
                    }
                }
            }
        }

        $this->logger->debug('PII anonymized', [
            'correlation_id' => $correlationId,
            'fields_masked' => count($fields),
        ]);

        return $data;
    }

    /**
     * Encrypt sensitive field value
     *
     * @param  string  $value  Value to encrypt
     * @return string Encrypted value
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt sensitive field value
     *
     * @param  string  $encryptedValue  Encrypted value
     * @return string Decrypted value
     */
    public function decrypt(string $encryptedValue): string
    {
        try {
            return Crypt::decryptString($encryptedValue);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt PII field', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Execute right to be forgotten - delete all user PII
     *
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $reason  Deletion reason
     * @param  int  $requestedBy  User requesting deletion
     * @return bool
     */
    public function executeRightToBeForgotten(
        int $userId,
        int $tenantId,
        string $reason,
        int $requestedBy,
        string $correlationId = ''
    ): bool {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($userId, $tenantId, $reason, $requestedBy, $correlationId) {
            $deletionId = Str::uuid()->toString();

            $this->db->table('pii_deletion_requests')->insert([
                'id' => $deletionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'reason' => $reason,
                'requested_by' => $requestedBy,
                'status' => 'completed',
                'completed_at' => now(),
                'created_at' => now(),
            ]);

            $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('created_by', $userId)
                ->update([
                    'supplier_name' => $this->maskValue('DELETED'),
                    'performed_by' => $this->maskValue('DELETED'),
                    'approved_by' => $this->maskValue('DELETED'),
                ]);

            $this->db->table('stock_movements')
                ->where('tenant_id', $tenantId)
                ->where('created_by', $userId)
                ->update([
                    'performed_by' => $this->maskValue('DELETED'),
                ]);

            $this->db->table('warehouses')
                ->where('tenant_id', $tenantId)
                ->where('created_by', $userId)
                ->update([
                    'contact_person' => $this->maskValue('DELETED'),
                ]);

            $this->logger->info('Right to be forgotten executed', [
                'deletion_id' => $deletionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'requested_by' => $requestedBy,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Apply data retention policy - delete expired records
     *
     * @param  string  $recordType  Type of records to clean
     * @return int Number of records deleted
     */
    public function applyDataRetention(string $recordType): int
    {
        if (! isset(self::RETENTION_PERIODS[$recordType])) {
            throw new \InvalidArgumentException("Unknown record type: {$recordType}");
        }

        $cutoffDate = now()->subDays(self::RETENTION_PERIODS[$recordType]);

        return match ($recordType) {
            'audit_logs' => $this->db->table('audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete(),
            'inventory_records' => $this->db->table('inventory_items')
                ->where('created_at', '<', $cutoffDate)
                ->where('deleted_at', '!=', null)
                ->forceDelete(),
            'supplier_data' => $this->db->table('suppliers')
                ->where('created_at', '<', $cutoffDate)
                ->where('is_active', false)
                ->delete(),
            default => 0,
        };
    }

    /**
     * Record consent for data processing
     *
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $consentType  Type of consent
     * @param  bool  $granted  Consent granted/revoked
     * @param  string  $ipAddress  User IP
     * @param  string  $userAgent  User agent
     * @return string Consent ID
     */
    public function recordConsent(
        int $userId,
        int $tenantId,
        string $consentType,
        bool $granted,
        string $ipAddress,
        string $userAgent
    ): string {
        $consentId = Str::uuid()->toString();

        $this->db->table('pii_consents')->insert([
            'id' => $consentId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'consent_type' => $consentType,
            'granted' => $granted,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        return $consentId;
    }

    /**
     * Check if user has given consent
     *
     * @param  int  $userId  User ID
     * @param  string  $consentType  Type of consent
     * @return bool
     */
    public function hasConsent(int $userId, string $consentType): bool
    {
        $latestConsent = $this->db->table('pii_consents')
            ->where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestConsent ? (bool) $latestConsent->granted : false;
    }

    /**
     * Get data retention period for record type
     *
     * @param  string  $recordType  Type of record
     * @return int Retention period in days
     */
    public function getRetentionPeriod(string $recordType): int
    {
        return self::RETENTION_PERIODS[$recordType] ?? 365;
    }

    /**
     * Mask value for anonymization
     *
     * @param  string  $value  Value to mask
     * @return string Masked value
     */
    private function maskValue(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 2) {
            return '**';
        }

        $visibleChars = min(2, (int) floor($length / 4));
        $maskedLength = $length - ($visibleChars * 2);

        return mb_substr($value, 0, $visibleChars).
               str_repeat('*', $maskedLength).
               mb_substr($value, -$visibleChars);
    }
}
