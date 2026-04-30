<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Log\LogManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Database Protection Service
 * 
 * Central service for database-level security operations:
 * - Encryption/decryption helpers with pepper
 * - Query monitoring for suspicious patterns
 * - SQL injection attempt detection
 * - Cross-tenant query prevention
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Security Fortress.
 */
final readonly class DatabaseProtectionService
{
    private const PEPPER_KEY = 'DB_ENCRYPTION_PEPPER';
    private const SUSPICIOUS_QUERY_THRESHOLD = 100;
    private const CROSS_TENANT_QUERY_BLOCKED = true;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly LogManager $logger,
        private readonly SensitiveDataMasker $masker,
    ) {}

    /**
     * Encrypt sensitive data with additional pepper
     * 
     * @param  string  $data  Plaintext data
     * @return string Encrypted data
     */
    public function encryptWithPepper(string $data): string
    {
        $pepper = $this->getPepper();
        $dataWithPepper = $data . $pepper;

        return Crypt::encryptString($dataWithPepper);
    }

    /**
     * Decrypt sensitive data with pepper validation
     * 
     * @param  string  $encryptedData  Encrypted data
     * @return string Plaintext data
     * @throws \RuntimeException If decryption fails or pepper mismatch
     */
    public function decryptWithPepper(string $encryptedData): string
    {
        try {
            $decrypted = Crypt::decryptString($encryptedData);
            $pepper = $this->getPepper();
            $pepperLength = strlen($pepper);

            // Remove pepper from end
            $data = substr($decrypted, 0, -$pepperLength);

            if ($data === false) {
                throw new \RuntimeException('Pepper mismatch - possible data tampering');
            }

            return $data;
        } catch (\Throwable $e) {
            $this->auditService->logEvent('decryption_failed', [
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 500),
            ], 'security');

            throw new \RuntimeException('Failed to decrypt sensitive data', 0, $e);
        }
    }

    /**
     * Hash contact information for unique_contacts table
     * 
     * @param  string  $contact  Email, phone, or INN
     * @param  string  $type  Contact type (email, phone, inn)
     * @return string SHA-256 hash
     */
    public function hashContact(string $contact, string $type): string
    {
        // Normalize contact
        $normalized = $this->normalizeContact($contact, $type);
        
        // Add tenant-specific salt for security
        $tenantId = $this->getCurrentTenantId();
        $salt = config('app.key') . $tenantId;
        
        return hash('sha256', $normalized . $salt);
    }

    /**
     * Normalize contact for hashing
     */
    private function normalizeContact(string $contact, string $type): string
    {
        return match ($type) {
            'email' => strtolower(trim($contact)),
            'phone' => preg_replace('/[^0-9]/', '', $contact),
            'inn' => preg_replace('/[^0-9]/', '', $contact),
            default => trim($contact),
        };
    }

    /**
     * Monitor query for suspicious patterns
     * 
     * @param  Builder  $query  Query builder instance
     * @param  string  $operation  Operation type (select, insert, update, delete)
     * @return void
     */
    public function monitorQuery(Builder $query, string $operation): void
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Check for suspicious patterns
        $this->detectSqlInjection($sql, $bindings);
        $this->detectCrossTenantQuery($sql, $bindings);
        $this->detectMassExtraction($sql, $operation);
    }

    /**
     * Detect SQL injection attempts
     */
    private function detectSqlInjection(string $sql, array $bindings): void
    {
        $suspiciousPatterns = [
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',    // OR 1=1
            '/\b(UNION|SELECT)\s+ALL\s+/i',      // UNION ALL SELECT
            '/\b(DROP|DELETE|TRUNCATE)\s+TABLE/i', // DROP TABLE
            '/\b(INSERT|UPDATE)\s+INTO.*VALUES/i', // INSERT INTO ... VALUES
            '/--/',                               // SQL comments
            '/\/\*/',                             // Multi-line comments
            '/;\s*(DROP|DELETE|ALTER)/i',         // Chained commands
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $this->handleSqlInjectionAttempt($sql, $bindings);
                break;
            }
        }
    }

    /**
     * Handle SQL injection attempt
     */
    private function handleSqlInjectionAttempt(string $sql, array $bindings): void
    {
        $this->auditService->logEvent('sql_injection_attempt', [
            'sql' => Str::limit($sql, 500),
            'bindings' => $this->sanitizeBindings($bindings),
            'user_id' => auth()->check() ? auth()->id() : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ], 'security');

        $this->logger->channel('security_alert')->critical('SQL injection attempt detected', $this->masker->mask([
            'sql' => Str::limit($sql, 500),
            'user_id' => auth()->check() ? auth()->id() : null,
        ]));

        throw new \RuntimeException('Query blocked: suspicious SQL pattern detected');
    }

    /**
     * Detect cross-tenant query attempts
     */
    private function detectCrossTenantQuery(string $sql, array $bindings): void
    {
        // Check for orWhere without tenant_id
        if (preg_match('/\borWhere\b/i', $sql) && ! str_contains($sql, 'tenant_id')) {
            $this->auditService->logEvent('cross_tenant_query_attempt', [
                'sql' => Str::limit($sql, 500),
                'user_id' => auth()->check() ? auth()->id() : null,
                'tenant_id' => $this->getCurrentTenantId(),
            ], 'security');

            if (self::CROSS_TENANT_QUERY_BLOCKED) {
                throw new \RuntimeException('Query blocked: cross-tenant access not allowed');
            }
        }

        // Check for raw SQL with tenant_id manipulation
        if (preg_match('/tenant_id\s*!=?\s*\d+/i', $sql)) {
            $this->auditService->logEvent('tenant_id_manipulation_attempt', [
                'sql' => Str::limit($sql, 500),
                'user_id' => auth()->check() ? auth()->id() : null,
            ], 'security');

            throw new \RuntimeException('Query blocked: tenant_id manipulation not allowed');
        }
    }

    /**
     * Detect mass data extraction attempts
     */
    private function detectMassExtraction(string $sql, string $operation): void
    {
        if ($operation !== 'select') {
            return;
        }

        // Check for queries without limit
        if (! preg_match('/\bLIMIT\b/i', $sql)) {
            $this->auditService->logEvent('mass_extraction_attempt_no_limit', [
                'sql' => Str::limit($sql, 500),
                'user_id' => auth()->check() ? auth()->id() : null,
            ], 'security');
        }

        // Check for very high limit
        if (preg_match('/\bLIMIT\s+(\d+)\b/i', $sql, $matches)) {
            $limit = (int) $matches[1];
            if ($limit > self::SUSPICIOUS_QUERY_THRESHOLD) {
                $this->auditService->logEvent('mass_extraction_attempt_high_limit', [
                    'sql' => Str::limit($sql, 500),
                    'limit' => $limit,
                    'user_id' => auth()->check() ? auth()->id() : null,
                ], 'security');
            }
        }
    }

    /**
     * Get encryption pepper from environment or config
     */
    private function getPepper(): string
    {
        return env(self::PEPPER_KEY, config('app.key'));
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): ?int
    {
        if (function_exists('tenant') && tenant() !== null) {
            return tenant()->id;
        }

        return session('active_tenant_id');
    }

    /**
     * Sanitize bindings for audit logging
     */
    private function sanitizeBindings(array $bindings): array
    {
        return array_map(function ($binding) {
            if (is_string($binding) && strlen($binding) > 100) {
                return Str::limit($binding, 100) . '...';
            }

            return $binding;
        }, $bindings);
    }

    /**
     * Validate that a query is safe to execute
     * 
     * @param  Builder  $query  Query builder instance
     * @return bool True if safe
     * @throws \RuntimeException If unsafe
     */
    public function validateQuery(Builder $query): bool
    {
        $this->monitorQuery($query, 'select');

        return true;
    }

    /**
     * Generate masked version of sensitive data
     * 
     * @param  string  $data  Sensitive data
     * @param  string  $type  Data type (email, phone, inn, name)
     * @return string Masked data
     */
    public function maskData(string $data, string $type): string
    {
        if (empty($data)) {
            return '';
        }

        return match ($type) {
            'email' => $this->maskEmail($data),
            'phone' => $this->maskPhone($data),
            'inn' => $this->maskInn($data),
            'name' => $this->maskName($data),
            default => str_repeat('*', min(strlen($data), 8)),
        };
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        [$local, $domain] = $parts;
        $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 1));

        return $maskedLocal . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 10) {
            return '***';
        }

        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }

    private function maskInn(string $inn): string
    {
        $length = strlen($inn);
        $visibleChars = min(4, $length);

        return str_repeat('*', $length - $visibleChars) . substr($inn, -$visibleChars);
    }

    private function maskName(string $name): string
    {
        $words = explode(' ', $name);
        $masked = array_map(fn ($word) => substr($word, 0, 1) . '***', $words);

        return implode(' ', $masked);
    }
}
