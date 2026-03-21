<?php declare(strict_types=1);

namespace App\Domains\MedicalSupplies\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class PrescriptionService
{
    public function __construct()
    {
    }

    public function validatePrescription(string $prescriptionCode, int $medicineId, string $correlationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'validatePrescription'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL validatePrescription', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'validatePrescription'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL validatePrescription', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'validatePrescription'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL validatePrescription', ['domain' => __CLASS__]);

        try {
            return DB::transaction(function () use ($prescriptionCode, $medicineId, $correlationId) {
                $prescription = DB::table('prescriptions')
                    ->where('code', $prescriptionCode)
                    ->where('medicine_id', $medicineId)
                    ->where('is_used', false)
                    ->lockForUpdate()
                    ->first();

                if (!$prescription) {
                    throw new \Exception('Prescription not found or already used');
                }

                // Марк как использованный
                DB::table('prescriptions')
                    ->where('id', $prescription->id)
                    ->update(['is_used' => true, 'used_at' => now()]);

                Log::channel('audit')->info('Prescription validated', [
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicineId,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Prescription validation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
