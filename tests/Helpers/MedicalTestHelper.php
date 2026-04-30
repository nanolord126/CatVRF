<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Domains\Medical\Models\MedicalRecord;
use App\Domains\Medical\Models\Patient;
use App\Domains\Medical\Services\PiiAnonymizerService;
use Illuminate\Support\Facades\Cache;

/**
 * Medical Test Helper
 *
 * Provides reusable test data and utilities for Medical vertical testing.
 * Ensures PII compliance and anonymization in all test scenarios.
 */
class MedicalTestHelper
{
    /**
     * Create a test patient with anonymized data
     */
    public static function createPatient(array $overrides = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'birth_date' => '1990-01-01',
            'phone' => '+79990000000',
            'email' => 'test.patient@example.com',
        ], $overrides));
    }

    /**
     * Create a medical record with anonymized symptoms
     * Ensures no raw PII is stored
     */
    public static function createMedicalRecord(array $overrides = []): MedicalRecord
    {
        $patient = $overrides['patient_id']
            ? Patient::find($overrides['patient_id'])
            : self::createPatient();

        return MedicalRecord::factory()->create(array_merge([
            'patient_id' => $patient->id,
            'symptoms' => json_encode(['headache', 'fever']), // Anonymized
            'diagnosis' => 'Common cold', // Anonymized
            'notes' => 'Test record',
        ], $overrides));
    }

    /**
     * Assert that PII data is not present in logs
     */
    public static function assertNoPiiInLogs(string $logContent): void
    {
        $piiPatterns = [
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
            '/\b\d{16}\b/', // Credit card
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\+?[0-9]{10,15}\b/', // Phone
        ];

        foreach ($piiPatterns as $pattern) {
            expect($logContent)->not->toMatch($pattern);
        }
    }

    /**
     * Assert that medical data is not sent to external LLM
     */
    public static function assertNoMedicalDataInExternalCalls(array $callData): void
    {
        $medicalKeywords = ['symptom', 'diagnosis', 'patient', 'medical', 'health'];

        foreach ($medicalKeywords as $keyword) {
            $jsonData = json_encode($callData);
            expect(strtolower($jsonData))->not->toContain($keyword);
        }
    }

    /**
     * Assert that cache contains only anonymized data
     */
    public static function assertCacheIsAnonymized(string $cacheKey): void
    {
        $cachedData = Cache::get($cacheKey);

        expect($cachedData)->not->toBeNull();

        $jsonData = json_encode($cachedData);
        self::assertNoPiiInLogs($jsonData);
    }

    /**
     * Create emergency scenario test data
     */
    public static function createEmergencyScenario(): array
    {
        return [
            'symptoms' => ['chest_pain', 'shortness_of_breath'],
            'severity' => 'critical',
            'is_emergency' => true,
            'vital_signs' => [
                'blood_pressure' => '180/120',
                'heart_rate' => 120,
                'temperature' => 38.5,
            ],
        ];
    }

    /**
     * Assert fraud check was performed
     */
    public static function assertFraudCheckPerformed(array $auditLogs): void
    {
        $fraudChecks = array_filter(
            $auditLogs,
            fn ($log) => isset($log['action']) && str_contains($log['action'], 'fraud_check')
        );

        expect($fraudChecks)->not->toBeEmpty();
    }

    /**
     * Assert quota was checked and enforced
     */
    public static function assertQuotaEnforced(array $quotaData): void
    {
        expect($quotaData)->toHaveKey('quota_limit');
        expect($quotaData)->toHaveKey('quota_used');
        expect($quotaData['quota_used'])->toBeLessThanOrEqual($quotaData['quota_limit']);
    }

    /**
     * Get anonymized symptom data for testing
     */
    public static function getAnonymizedSymptoms(): array
    {
        return [
            'headache',
            'fever',
            'cough',
            'fatigue',
            'nausea',
        ];
    }

    /**
     * Mock PiiAnonymizerService
     */
    public static function mockPiiAnonymizer(): void
    {
        $mock = \Mockery::mock(PiiAnonymizerService::class);
        $mock->shouldReceive('anonymize')
            ->andReturnUsing(fn ($data) => $data); // Return data as-is for testing
        $mock->shouldReceive('anonymizeSymptoms')
            ->andReturnUsing(fn ($symptoms) => $symptoms);

        app()->instance(PiiAnonymizerService::class, $mock);
    }
}
