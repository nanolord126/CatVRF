<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ЕГИСЗ Integration Service - ФЗ-323 Compliance
 *
 * Integrates with Unified State Information System in Healthcare:
 * - Medical license verification
 * - Doctor certification checks
 * - Prescription drug reporting
 * - Patient data exchange (anonymized)
 * - Medical service reporting
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class EGISZIntegrationService
{
    use WithAuditLogging;

    private const API_BASE_URL = 'https://api.egisz.rosminzdrav.ru/api/v2';
    private const TOKEN_CACHE_KEY = 'egisz:token';
    private const TOKEN_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Verify medical license
     *
     * @param  string  $licenseNumber  License number
     * @param  string  $inn  Organization INN
     * @return array Verification result
     */
    public function verifyMedicalLicense(string $licenseNumber, string $inn): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(self::API_BASE_URL.'/licenses/verify', [
                    'license_number' => $licenseNumber,
                    'inn' => $inn,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            // Cache verification result
            Cache::put(
                "egisz:license:{$licenseNumber}",
                $result,
                now()->addDays(7)
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify medical license in ЕГИСЗ', [
                'license_number' => $licenseNumber,
                'inn' => $inn,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify doctor certification
     *
     * @param  string  $certNumber  Certificate number
     * @param  string  $snils  Doctor SNILS
     * @return array Verification result
     */
    public function verifyDoctorCertification(string $certNumber, string $snils): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(self::API_BASE_URL.'/doctors/verify', [
                    'certificate_number' => $certNumber,
                    'snils' => $snils,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify doctor certification in ЕГИСЗ', [
                'cert_number' => $certNumber,
                'snils' => $snils,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Report prescription drug dispensing
     *
     * @param  int  $prescriptionId  Prescription ID
     * @param  string  $drugCode  Drug code
     * @param  string  $series  Drug series
     * @param  int  $quantity  Quantity dispensed
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User reporting
     * @return array Report result
     */
    public function reportPrescriptionDispensing(
        int $prescriptionId,
        string $drugCode,
        string $series,
        int $quantity,
        int $tenantId,
        int $userId
    ): array {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::API_BASE_URL.'/prescriptions/dispensing', [
                    'prescription_id' => $prescriptionId,
                    'drug_code' => $drugCode,
                    'series' => $series,
                    'quantity' => $quantity,
                    'dispensed_at' => now()->toIso8601String(),
                    'organization_id' => config('services.egisz.organization_id'),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            // Store report in database
            $this->db->table('egisz_reports')->insert([
                'id' => Str::uuid()->toString(),
                'report_type' => 'prescription_dispensing',
                'prescription_id' => $prescriptionId,
                'drug_code' => $drugCode,
                'status' => 'reported',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'egisz_prescription_reported',
                entityType: 'EGISZReport',
                entityId: $result['report_id'] ?? '',
                context: [
                    'prescription_id' => $prescriptionId,
                    'drug_code' => $drugCode,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to report prescription dispensing to ЕГИСЗ', [
                'prescription_id' => $prescriptionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Report medical service
     *
     * @param  int  $appointmentId  Appointment ID
     * @param  string  $serviceCode  Medical service code
     * @param  string  $result  Service result
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User reporting
     * @return array Report result
     */
    public function reportMedicalService(
        int $appointmentId,
        string $serviceCode,
        string $result,
        int $tenantId,
        int $userId
    ): array {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::API_BASE_URL.'/services/report', [
                    'appointment_id' => $appointmentId,
                    'service_code' => $serviceCode,
                    'result' => $result,
                    'performed_at' => now()->toIso8601String(),
                    'organization_id' => config('services.egisz.organization_id'),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            $resultData = $response->json();

            // Store report in database
            $this->db->table('egisz_reports')->insert([
                'id' => Str::uuid()->toString(),
                'report_type' => 'medical_service',
                'appointment_id' => $appointmentId,
                'service_code' => $serviceCode,
                'status' => 'reported',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'egisz_service_reported',
                entityType: 'EGISZReport',
                entityId: $resultData['report_id'] ?? '',
                context: [
                    'appointment_id' => $appointmentId,
                    'service_code' => $serviceCode,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $resultData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to report medical service to ЕГИСЗ', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get patient data from ЕГИСЗ (anonymized)
     *
     * @param  string  $policyNumber  Insurance policy number
     * @return array Patient data (anonymized)
     */
    public function getPatientData(string $policyNumber): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(self::API_BASE_URL.'/patients/info', [
                    'policy_number' => $policyNumber,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            // Anonymize PII data before returning
            return $this->anonymizePatientData($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get patient data from ЕГИСЗ', [
                'policy_number' => $policyNumber,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Sync drug database from ЕГИСЗ
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User syncing
     * @return array Sync result
     */
    public function syncDrugDatabase(int $tenantId, int $userId): array
    {
        $token = $this->getAuthToken();

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->get(self::API_BASE_URL.'/drugs/export', [
                    'date_from' => now()->subDays(1)->toDateString(),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "ЕГИСЗ API error: {$response->status()} - {$response->body()}"
                );
            }

            $drugs = $response->json('drugs', []);

            $synced = 0;
            foreach ($drugs as $drug) {
                $this->db->table('egisz_drugs')->updateOrInsert(
                    [
                        'drug_code' => $drug['code'],
                    ],
                    [
                        'name' => $drug['name'],
                        'trade_name' => $drug['trade_name'] ?? null,
                        'is_prescription' => $drug['is_prescription'] ?? false,
                        'is_narcotic' => $drug['is_narcotic'] ?? false,
                        'is_psychotropic' => $drug['is_psychotropic'] ?? false,
                        'updated_at' => now(),
                    ]
                );
                $synced++;
            }

            $this->logAction(
                action: 'egisz_drug_database_synced',
                entityType: 'EGISZDrugSync',
                entityId: (string) $tenantId,
                context: [
                    'drugs_synced' => $synced,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'drugs_synced' => $synced,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync drug database from ЕГИСЗ', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get authentication token from ЕГИСЗ
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            $response = Http::timeout(30)->post(self::API_BASE_URL.'/auth/token', [
                'client_id' => config('services.egisz.client_id'),
                'client_secret' => config('services.egisz.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Failed to get ЕГИСЗ auth token: {$response->status()}"
                );
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Anonymize patient data for privacy
     *
     * @param  array  $data  Patient data
     * @return array Anonymized data
     */
    private function anonymizePatientData(array $data): array
    {
        $piiFields = [
            'first_name',
            'last_name',
            'middle_name',
            'birth_date',
            'address',
            'phone',
            'email',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $piiFields, true) && is_string($value)) {
                $length = mb_strlen($value);
                if ($length > 2) {
                    $data[$key] = mb_substr($value, 0, 1).str_repeat('*', $length - 2).mb_substr($value, -1);
                } else {
                    $data[$key] = '**';
                }
            }
        }

        return $data;
    }
}
