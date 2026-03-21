<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class AppointmentService
{
    public function __construct(
        private ConsumableDeductionService $consumableService,
    ) {
    }

    /**
     * Создать запись на услугу с hold расходников
     */
    public function bookAppointment(
        int $masterId,
        int $serviceId,
        Carbon $dateTime,
        array $consumables,
        string $correlationId,
    ): Appointment {
        try {
            $appointment = DB::transaction(function () use ($masterId, $serviceId, $dateTime, $consumables, $correlationId) {
                $appointment = Appointment::create([
                    'master_id' => $masterId,
                    'service_id' => $serviceId,
                    'appointment_date' => $dateTime,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'tenant_id' => tenant()->id,
                ]);

                if (!empty($consumables)) {
                    $this->consumableService->reserveConsumables(
                        $appointment->id,
                        $consumables,
                        $correlationId,
                    );
                }

                Log::channel('audit')->info('Appointment booked', [
                    'appointment_id' => $appointment->id,
                    'master_id' => $masterId,
                    'service_id' => $serviceId,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });

            return $appointment;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Appointment booking failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить запись и отпустить расходники
     */
    public function cancelAppointment(int $appointmentId, array $consumables, string $correlationId): bool
    {
        try {
            DB::transaction(function () use ($appointmentId, $consumables, $correlationId) {
                $appointment = Appointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'cancelled']);

                if (!empty($consumables)) {
                    $this->consumableService->releaseConsumables(
                        $appointmentId,
                        $consumables,
                        $correlationId,
                    );
                }

                Log::channel('audit')->info('Appointment cancelled', [
                    'appointment_id' => $appointmentId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Appointment cancellation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить визит и списать расходники
     */
    public function completeAppointment(int $appointmentId, array $consumables, string $correlationId): bool
    {
        try {
            DB::transaction(function () use ($appointmentId, $consumables, $correlationId) {
                $appointment = Appointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'completed', 'completed_at' => now()]);

                if (!empty($consumables)) {
                    $this->consumableService->deductConsumables(
                        $appointmentId,
                        $consumables,
                        $correlationId,
                    );
                }

                Log::channel('audit')->info('Appointment completed', [
                    'appointment_id' => $appointmentId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Appointment completion failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
