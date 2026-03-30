<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\MedicalSupplies\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PrescriptionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
    DB::transaction(function () use ($prescriptionCode, $medicineId, $correlationId) {
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
