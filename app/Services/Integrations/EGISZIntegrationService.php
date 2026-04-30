<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * EGISZ Integration Service - ФЗ-323 Compliance
 *
 * Implements integration with ЕГИСЗ (Единая государственная информационная система
 * в сфере здравоохранения) for healthcare compliance:
 * - Patient data synchronization
 * - Prescription validation
 * - Medical product registration
 * - Regulatory reporting
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class EGISZIntegrationService
{
    private const API_VERSION = 'v1';
    private const ENDPOINT_PRESCRIPTIONS = '/prescriptions';
    private const ENDPOINT_PATIENTS = '/patients';
    private const ENDPOINT_MEDICINES = '/medicines';
    private const ENDPOINT_REPORTS = '/reports';

    public function __construct(
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly ?string $certificatePath = null,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Validate prescription with EGISZ
     *
     * @param  string  $prescriptionNumber  Prescription number
     * @param  string  $patientOms  Patient OMS number
     * @param  int  $tenantId  Tenant ID
     * @return array Validation result
     */
    public function validatePrescription(
        string $prescriptionNumber,
        string $patientOms,
        int $tenantId
    ): array {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'X-Tenant-ID' => $tenantId,
            ])->timeout(30)->post(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_PRESCRIPTIONS . '/validate',
                [
                    'prescription_number' => $prescriptionNumber,
                    'patient_oms' => $patientOms,
                ]
            );

            if (! $response->successful()) {
                $this->logger->error('EGISZ prescription validation failed', [
                    'prescription_number' => $prescriptionNumber,
                    'patient_oms' => $this->maskOms($patientOms),
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('EGISZ prescription validation failed');
            }

            $result = $response->json();

            $this->logger->info('EGISZ prescription validated', [
                'prescription_number' => $prescriptionNumber,
                'valid' => $result['valid'] ?? false,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('EGISZ prescription validation error', [
                'prescription_number' => $prescriptionNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if medicine requires prescription
     *
     * @param  string  $medicineCode  Medicine code
     * @return bool Requires prescription
     */
    public function requiresPrescription(string $medicineCode): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->get(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_MEDICINES . "/{$medicineCode}"
            );

            if (! $response->successful()) {
                // Default to requiring prescription for safety
                return true;
            }

            $medicine = $response->json();

            return $medicine['requires_prescription'] ?? true;
        } catch (\Exception $e) {
            $this->logger->warning('EGISZ medicine check failed, defaulting to prescription required', [
                'medicine_code' => $medicineCode,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * Register patient with EGISZ
     *
     * @param  array<string, mixed>  $patientData  Patient data
     * @param  int  $tenantId  Tenant ID
     * @return string Patient ID in EGISZ
     */
    public function registerPatient(array $patientData, int $tenantId): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'X-Tenant-ID' => $tenantId,
            ])->timeout(30)->post(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_PATIENTS,
                [
                    'oms_number' => $patientData['oms_number'],
                    'first_name' => $patientData['first_name'],
                    'last_name' => $patientData['last_name'],
                    'patronymic' => $patientData['patronymic'] ?? null,
                    'birth_date' => $patientData['birth_date'],
                    'gender' => $patientData['gender'],
                    'address' => $patientData['address'] ?? null,
                ]
            );

            if (! $response->successful()) {
                $this->logger->error('EGISZ patient registration failed', [
                    'oms_number' => $this->maskOms($patientData['oms_number']),
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('EGISZ patient registration failed');
            }

            $result = $response->json();

            $this->logger->info('EGISZ patient registered', [
                'egisz_patient_id' => $result['patient_id'],
                'oms_number' => $this->maskOms($patientData['oms_number']),
            ]);

            return $result['patient_id'];
        } catch (\Exception $e) {
            $this->logger->error('EGISZ patient registration error', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Submit regulatory report to EGISZ
     *
     * @param  string  $reportType  Report type
     * @param  array<string, mixed>  $reportData  Report data
     * @param  int  $tenantId  Tenant ID
     * @return string Report ID
     */
    public function submitReport(
        string $reportType,
        array $reportData,
        int $tenantId
    ): string {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'X-Tenant-ID' => $tenantId,
            ])->timeout(60)->post(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_REPORTS,
                [
                    'report_type' => $reportType,
                    'period_start' => $reportData['period_start'],
                    'period_end' => $reportData['period_end'],
                    'data' => $reportData['data'],
                ]
            );

            if (! $response->successful()) {
                $this->logger->error('EGISZ report submission failed', [
                    'report_type' => $reportType,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \RuntimeException('EGISZ report submission failed');
            }

            $result = $response->json();

            $this->logger->info('EGISZ report submitted', [
                'report_type' => $reportType,
                'report_id' => $result['report_id'],
            ]);

            return $result['report_id'];
        } catch (\Exception $e) {
            $this->logger->error('EGISZ report submission error', [
                'report_type' => $reportType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get medicine information from EGISZ
     *
     * @param  string  $medicineCode  Medicine code
     * @return array Medicine information
     */
    public function getMedicineInfo(string $medicineCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->get(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_MEDICINES . "/{$medicineCode}"
            );

            if (! $response->successful()) {
                $this->logger->warning('EGISZ medicine info retrieval failed', [
                    'medicine_code' => $medicineCode,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->warning('EGISZ medicine info retrieval error', [
                'medicine_code' => $medicineCode,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check medicine stock availability in EGISZ
     *
     * @param  string  $medicineCode  Medicine code
     * @param  int  $regionCode  Region code
     * @return array Stock availability
     */
    public function checkMedicineStock(string $medicineCode, int $regionCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(15)->get(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_MEDICINES . "/{$medicineCode}/stock",
                [
                    'region_code' => $regionCode,
                ]
            );

            if (! $response->successful()) {
                $this->logger->warning('EGISZ medicine stock check failed', [
                    'medicine_code' => $medicineCode,
                    'region_code' => $regionCode,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->warning('EGISZ medicine stock check error', [
                'medicine_code' => $medicineCode,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Synchronize patient data with EGISZ
     *
     * @param  string  $egiszPatientId  EGISZ patient ID
     * @param  int  $tenantId  Tenant ID
     * @return array Synchronized patient data
     */
    public function syncPatientData(string $egiszPatientId, int $tenantId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'X-Tenant-ID' => $tenantId,
            ])->timeout(30)->get(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_PATIENTS . "/{$egiszPatientId}"
            );

            if (! $response->successful()) {
                $this->logger->error('EGISZ patient sync failed', [
                    'egisz_patient_id' => $egiszPatientId,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException('EGISZ patient sync failed');
            }

            $patientData = $response->json();

            $this->logger->info('EGISZ patient data synchronized', [
                'egisz_patient_id' => $egiszPatientId,
            ]);

            return $patientData;
        } catch (\Exception $e) {
            $this->logger->error('EGISZ patient sync error', [
                'egisz_patient_id' => $egiszPatientId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get report status from EGISZ
     *
     * @param  string  $reportId  Report ID
     * @return array Report status
     */
    public function getReportStatus(string $reportId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->get(
                "{$this->apiUrl}/" . self::API_VERSION . self::ENDPOINT_REPORTS . "/{$reportId}/status"
            );

            if (! $response->successful()) {
                $this->logger->warning('EGISZ report status check failed', [
                    'report_id' => $reportId,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->warning('EGISZ report status check error', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Mask OMS number for logging (152-ФЗ compliance)
     *
     * @param  string  $oms  OMS number
     * @return string Masked OMS
     */
    private function maskOms(string $oms): string
    {
        if (strlen($oms) <= 4) {
            return str_repeat('*', strlen($oms));
        }

        return substr($oms, 0, 2) . str_repeat('*', strlen($oms) - 4) . substr($oms, -2);
    }
}
