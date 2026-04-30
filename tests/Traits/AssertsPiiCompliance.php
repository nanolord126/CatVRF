<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * PII Compliance Assertions Trait
 *
 * Provides comprehensive assertions for PII (Personally Identifiable Information)
 * compliance testing according to Russian Federal Law 152-FZ and FZ-323.
 * Ensures no sensitive medical data leaks to logs, cache, external APIs, or storage.
 */
trait AssertsPiiCompliance
{
    /**
     * Assert that data contains no PII patterns
     */
    public function assertNoPiiPatterns(mixed $data): void
    {
        $stringData = is_string($data) ? $data : json_encode($data);

        $piiPatterns = [
            // Russian phone numbers
            '/\+7[0-9]{10}/',
            '/8[0-9]{10}/',
            // Email addresses
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            // Russian passport series/number
            '/\b\d{4}\s?\d{6}\b/',
            // Russian SNILS
            '/\b\d{3}-\d{3}-\d{3}\s?\d{2}\b/',
            // Russian INN
            '/\b\d{10}\b/',
            '/\b\d{12}\b/',
            // Credit card numbers (basic pattern)
            '/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{4}\b/',
            // Full name patterns (basic)
            '/[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+/u',
            // Address patterns
            '/г\.\s*[А-ЯЁ][а-яё]+/u',
            '/ул\.\s*[А-ЯЁ][а-яё]+/u',
            '/д\.\s*\d+/u',
            '/кв\.\s*\d+/u',
        ];

        foreach ($piiPatterns as $pattern) {
            expect($stringData)->not->toMatch($pattern, "PII pattern detected: {$pattern}");
        }
    }

    /**
     * Assert that medical symptoms are anonymized
     */
    public function assertSymptomsAnonymized(mixed $symptoms): void
    {
        $stringData = is_string($symptoms) ? $symptoms : json_encode($symptoms);

        // Specific medical PII patterns that should be anonymized
        $medicalPiiPatterns = [
            // Patient name in symptoms
            '/пациент\s+[А-ЯЁ][а-яё]+/ui',
            // Full diagnosis with patient details
            '/диагноз\s+для\s+[А-ЯЁ][а-яё]+/ui',
            // Personal medical history references
            '/история\s+болезни\s+[А-ЯЁ][а-яё]+/ui',
            // Policy number (OMS)
            '/\b\d{16}\b/', // OMS policy format
        ];

        foreach ($medicalPiiPatterns as $pattern) {
            expect($stringData)->not->toMatch($pattern, "Medical PII pattern detected: {$pattern}");
        }
    }

    /**
     * Assert that logs contain no PII
     */
    public function assertLogsContainNoPii(): void
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return;
        }

        $logContent = file_get_contents($logFile);
        $this->assertNoPiiPatterns($logContent);
    }

    /**
     * Assert that cache contains no PII
     */
    public function assertCacheContainsNoPii(string $cacheKey): void
    {
        $cachedData = Cache::get($cacheKey);

        if ($cachedData === null) {
            return;
        }

        $this->assertNoPiiPatterns($cachedData);
    }

    /**
     * Assert that storage contains no PII
     */
    public function assertStorageContainsNoPii(string $path): void
    {
        $files = Storage::files($path);

        foreach ($files as $file) {
            $content = Storage::get($file);
            $this->assertNoPiiPatterns($content);
        }
    }

    /**
     * Assert that external API calls contain no PII
     */
    public function assertExternalApiCallContainsNoPii(array $apiCallData): void
    {
        $this->assertNoPiiPatterns($apiCallData);
        $this->assertSymptomsAnonymized($apiCallData);
    }

    /**
     * Assert that LLM prompts contain no raw medical data
     */
    public function assertLlmPromptContainsNoRawMedicalData(string $prompt): void
    {
        // Medical data that should never be sent to external LLMs
        $forbiddenMedicalData = [
            'симптомы пациента',
            'диагноз пациента',
            'история болезни',
            'медицинская карта',
            'результаты анализов',
            'personal health information',
            'patient symptoms',
            'patient diagnosis',
            'medical history',
        ];

        foreach ($forbiddenMedicalData as $term) {
            expect(strtolower($prompt))->not->toContain(
                $term,
                "LLM prompt contains forbidden medical data: {$term}"
            );
        }
    }

    /**
     * Assert that data is properly anonymized
     */
    public function assertDataAnonymized(array $originalData, array $anonymizedData): void
    {
        // Check that direct PII fields are removed or masked
        $piiFields = ['first_name', 'last_name', 'middle_name', 'phone', 'email', 'passport', 'snils', 'inn'];

        foreach ($piiFields as $field) {
            if (isset($originalData[$field])) {
                if (isset($anonymizedData[$field])) {
                    // Field exists but should be masked
                    expect($anonymizedData[$field])->not->toBe($originalData[$field]);
                } else {
                    // Field should be removed
                    expect($anonymizedData)->not->toHaveKey($field);
                }
            }
        }
    }

    /**
     * Assert that audit logs contain no sensitive PII
     */
    public function assertAuditLogsContainNoPii(array $auditLogs): void
    {
        foreach ($auditLogs as $log) {
            $this->assertNoPiiPatterns($log);

            // Audit logs should contain user IDs, not personal data
            expect($log)->not->toHaveKey('first_name');
            expect($log)->not->toHaveKey('last_name');
            expect($log)->not->toHaveKey('phone');
            expect($log)->not->toHaveKey('email');
        }
    }

    /**
     * Assert that database queries contain no PII in logs
     */
    public function assertDatabaseQueriesContainNoPii(): void
    {
        // This would typically check the query log
        // For now, we'll check if query logging is enabled
        if (config('database.log')) {
            $queryLog = DB::getQueryLog();

            foreach ($queryLog as $query) {
                $this->assertNoPiiPatterns($query['query']);
                $this->assertNoPiiPatterns($query['bindings'] ?? []);
            }
        }
    }

    /**
     * Assert that encryption is used for sensitive fields
     */
    public function assertSensitiveFieldsEncrypted(array $data, array $sensitiveFields): void
    {
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                // Encrypted data should be longer and not readable
                expect(strlen($data[$field]))->toBeGreaterThan(20);
                expect($data[$field])->not->toMatch('/^[А-ЯЁA-Za-z]+$/u');
            }
        }
    }

    /**
     * Assert that data retention policy is followed
     */
    public function assertDataRetentionFollowed(string $tableName, int $maxRetentionDays): void
    {
        $cutoffDate = now()->subDays($maxRetentionDays);

        $oldRecords = DB::table($tableName)
            ->where('created_at', '<', $cutoffDate)
            ->count();

        expect($oldRecords)->toBe(
            0,
            "Data retention policy violated: found {$oldRecords} records older than {$maxRetentionDays} days"
        );
    }

    /**
     * Assert that consent is present for data processing
     */
    public function assertConsentPresent(int $userId, string $consentType): void
    {
        $consent = DB::table('user_consents')
            ->where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('granted', true)
            ->first();

        expect($consent)->not->BeNull(
            "Consent of type '{$consentType}' not found for user {$userId}"
        );
    }

    /**
     * Assert that data access is logged
     */
    public function assertDataAccessLogged(int $userId, string $resourceType): void
    {
        $accessLog = DB::table('data_access_logs')
            ->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('created_at', '>=', now()->subMinute())
            ->first();

        expect($accessLog)->not->BeNull(
            "Data access not logged for user {$userId} accessing {$resourceType}"
        );
    }

    /**
     * Assert that emergency data is handled with special care
     */
    public function assertEmergencyDataCompliance(array $emergencyData): void
    {
        // Emergency data can contain more information but still needs PII protection
        $this->assertNoPiiPatterns($emergencyData);

        // Emergency data should have special flags
        expect($emergencyData)->toHaveKey('is_emergency');
        expect($emergencyData['is_emergency'])->toBeTrue();

        // Emergency data should have audit trail
        expect($emergencyData)->toHaveKey('emergency_timestamp');
    }

    /**
     * Mock PII anonymizer service
     */
    protected function mockPiiAnonymizer(): void
    {
        $mock = \Mockery::mock('overload:App\Domains\Medical\Services\PiiAnonymizerService');

        $mock->shouldReceive('anonymize')
            ->andReturnUsing(function ($data) {
                if (is_array($data)) {
                    return array_map(fn ($v) => str_repeat('*', strlen($v)), $data);
                }

                return str_repeat('*', strlen($data));
            });

        $mock->shouldReceive('anonymizeSymptoms')
            ->andReturnUsing(function ($symptoms) {
                return array_map(fn ($s) => 'anonymized_'.md5($s), (array) $symptoms);
            });
    }

    /**
     * Clear log file for testing
     */
    protected function clearLogFile(): void
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
    }

    /**
     * Get log content for assertions
     */
    protected function getLogContent(): string
    {
        $logFile = storage_path('logs/laravel.log');

        return file_exists($logFile) ? file_get_contents($logFile) : '';
    }
}
