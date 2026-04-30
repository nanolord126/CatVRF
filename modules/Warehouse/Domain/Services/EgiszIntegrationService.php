<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Warehouse\Domain\Exceptions\ChestnyZnakException;

/**
 * ЕГИСЗ Integration Service for ФЗ-323 compliance
 * 
 * Сервис обеспечивает базовую интеграцию с Единой государственной информационной системой
 * в сфере здравоохранения (ЕГИСЗ)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class EgiszIntegrationService
{
    private const BASE_URL = 'https://egisz.rosminzdrav.gov.ru/api';
    private const API_VERSION = 'v1';

    public function __construct(
        private readonly string $apiKey,
        private readonly bool $enabled = false
    ) {}

    /**
     * Проверка лицензии медицинского учреждения через ЕГИСЗ
     */
    public function verifyMedicalLicense(string $licenseNumber, string $ogrn): array
    {
        if (!$this->enabled) {
            $this->logMockCall('verifyMedicalLicense', compact('licenseNumber', 'ogrn'));
            return [
                'valid' => true,
                'license_number' => $licenseNumber,
                'status' => 'active',
                'expiry_date' => now()->addYears(5)->toDateString(),
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->get(self::BASE_URL . '/' . self::API_VERSION . '/licenses/verify', [
                'license_number' => $licenseNumber,
                'ogrn' => $ogrn,
            ]);

            if (!$response->successful()) {
                throw new EgiszException('Failed to verify license: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ЕГИСЗ license verification failed', [
                'error' => $e->getMessage(),
                'license_number' => $licenseNumber,
            ]);
            throw new EgiszException('ЕГИСЗ integration failed: ' . $e->getMessage());
        }
    }

    /**
     * Регистрация медицинского учреждения в ЕГИСЗ
     */
    public function registerMedicalOrganization(array $organizationData): array
    {
        if (!$this->enabled) {
            $this->logMockCall('registerMedicalOrganization', $organizationData);
            return [
                'organization_id' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'registered',
                'registered_at' => now()->toIso8601String(),
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::BASE_URL . '/' . self::API_VERSION . '/organizations', $organizationData);

            if (!$response->successful()) {
                throw new EgiszException('Failed to register organization: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ЕГИСЗ organization registration failed', [
                'error' => $e->getMessage(),
            ]);
            throw new EgiszException('ЕГИСЗ integration failed: ' . $e->getMessage());
        }
    }

    /**
     * Отправка данных о лекарственных средствах в ЕГИСЗ
     */
    public function submitPharmaceuticalData(array $drugData): array
    {
        if (!$this->enabled) {
            $this->logMockCall('submitPharmaceuticalData', $drugData);
            return [
                'submission_id' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'submitted',
                'submitted_at' => now()->toIso8601String(),
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::BASE_URL . '/' . self::API_VERSION . '/pharmaceuticals', $drugData);

            if (!$response->successful()) {
                throw new EgiszException('Failed to submit pharmaceutical data: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ЕГИСЗ pharmaceutical data submission failed', [
                'error' => $e->getMessage(),
            ]);
            throw new EgiszException('ЕГИСЗ integration failed: ' . $e->getMessage());
        }
    }

    /**
     * Получение информации о лекарственном препарате из ЕГИСЗ
     */
    public function getDrugInfo(string $drugCode): array
    {
        if (!$this->enabled) {
            $this->logMockCall('getDrugInfo', compact('drugCode'));
            return [
                'code' => $drugCode,
                'name' => 'Mock Drug Name',
                'manufacturer' => 'Mock Manufacturer',
                'registration_date' => '2020-01-01',
                'expiry_date' => '2030-01-01',
                'requires_prescription' => true,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->get(self::BASE_URL . '/' . self::API_VERSION . '/pharmaceuticals/' . $drugCode);

            if (!$response->successful()) {
                throw new EgiszException('Failed to get drug info: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ЕГИСЗ drug info retrieval failed', [
                'error' => $e->getMessage(),
                'drug_code' => $drugCode,
            ]);
            throw new EgiszException('ЕГИСЗ integration failed: ' . $e->getMessage());
        }
    }

    /**
     * Проверка статуса интеграции
     */
    public function checkHealth(): array
    {
        if (!$this->enabled) {
            return [
                'status' => 'disabled',
                'message' => 'ЕГИСЗ integration is disabled',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->timeout(10)->get(self::BASE_URL . '/health');

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->handlerStats()?['total_time'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Логирование mock-вызовов (для тестирования)
     */
    private function logMockCall(string $method, array $params): void
    {
        Log::info('ЕГИСЗ mock call (integration disabled)', [
            'method' => $method,
            'params' => $params,
        ]);
    }
}
