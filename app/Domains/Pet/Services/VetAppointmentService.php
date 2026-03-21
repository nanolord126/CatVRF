<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class VetAppointmentService
{
    public function __construct()
    {
    }

    /**
     * Забронировать приём в клинике
     */
    public function bookVetAppointment(
        int $vetId,
        int $clinicId,
        string $petName,
        string $petType,
        string $correlationId,
    ): int {
        try {
            $appointmentId = DB::transaction(function () use ($vetId, $clinicId, $petName, $petType, $correlationId) {
                $appointmentId = DB::table('pet_appointments')->insertGetId([
                    'vet_id' => $vetId,
                    'clinic_id' => $clinicId,
                    'pet_name' => $petName,
                    'pet_type' => $petType,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Vet appointment booked', [
                    'appointment_id' => $appointmentId,
                    'vet_id' => $vetId,
                    'pet_type' => $petType,
                    'correlation_id' => $correlationId,
                ]);

                return $appointmentId;
            });

            return $appointmentId;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Vet appointment booking failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить приём и списать расходники (лекарства, инструменты)
     */
    public function completeVetVisit(int $appointmentId, array $supplies, string $correlationId): bool
    {
        try {
            DB::transaction(function () use ($appointmentId, $supplies, $correlationId) {
                // Обновить статус приема
                DB::table('pet_appointments')
                    ->where('id', $appointmentId)
                    ->update(['status' => 'completed', 'completed_at' => now()]);

                // Списать расходники
                foreach ($supplies as $supplierId => $quantity) {
                    DB::table('pet_supplies')
                        ->where('id', $supplierId)
                        ->lockForUpdate()
                        ->decrement('stock', $quantity);
                }

                Log::channel('audit')->info('Vet visit completed', [
                    'appointment_id' => $appointmentId,
                    'supplies_count' => count($supplies),
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Vet visit completion failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
