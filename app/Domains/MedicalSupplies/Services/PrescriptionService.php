<?php declare(strict_types=1);

namespace App\Domains\MedicalSupplies\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class PrescriptionService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function validatePrescription(string $prescriptionCode, int $medicineId, string $correlationId): bool
    {
        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($prescriptionCode, $medicineId, $correlationId) {
                $prescription = $this->db->table('prescriptions')
                    ->where('code', $prescriptionCode)
                    ->where('medicine_id', $medicineId)
                    ->where('is_used', false)
                    ->lockForUpdate()
                    ->first();

                if (!$prescription) {
                    throw new \Exception('Prescription not found or already used');
                }

                // Марк как использованный
                $this->db->table('prescriptions')
                    ->where('id', $prescription->id)
                    ->update(['is_used' => true, 'used_at' => now()]);

                $this->log->channel('audit')->info('Prescription validated', [
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicineId,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Prescription validation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
