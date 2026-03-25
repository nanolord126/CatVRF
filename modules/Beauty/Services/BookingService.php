<?php declare(strict_types=1);

namespace App\Modules\Beauty\Services;

use App\Modules\Beauty\Models\Appointment;
use App\Modules\Beauty\Models\BeautySalon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DomainException;
use Throwable;
use Carbon\Carbon;

/**
 * Сервис управления записями (бронированием) услуг красоты.
 * Production 2026.
 */
final class BookingService
{
    /**
     * Создать запись на услугу.
     */
    public function createAppointment(
        int $salonId,
        int $serviceId,
        int $masterId,
        int $clientId,
        int $tenantId,
        string $dateTime,
        ?string $notes = null,
        mixed $correlationId = null,
    ): Appointment {
        $correlationId ??= Str::uuid();

        try {
            $this->log->channel('audit')->info('beauty.booking.create.start', [
                'correlation_id' => $correlationId,
                'salon_id' => $salonId,
                'service_id' => $serviceId,
                'datetime' => $dateTime,
            ]);

            $appointment = $this->db->transaction(function () use (
                $salonId,
                $serviceId,
                $masterId,
                $clientId,
                $tenantId,
                $dateTime,
                $notes,
                $correlationId,
            ) {
                // Проверка конфликта времени
                $conflict = Appointment::where('master_id', $masterId)
                    ->where('datetime', $dateTime)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if ($conflict) {
                    throw new DomainException('Мастер занят в это время');
                }

                return Appointment::create([
                    'salon_id' => $salonId,
                    'service_id' => $serviceId,
                    'master_id' => $masterId,
                    'client_id' => $clientId,
                    'tenant_id' => $tenantId,
                    'datetime' => $dateTime,
                    'status' => 'pending',
                    'notes' => $notes,
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['appointment', 'pending'],
                ]);
            });

            $this->log->channel('audit')->info('beauty.booking.create.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            return $appointment;
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.booking.create.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить запись.
     */
    public function cancelAppointment(
        Appointment $appointment,
        mixed $correlationId = null,
    ): Appointment {
        $correlationId ??= Str::uuid();

        try {
            $this->log->channel('audit')->info('beauty.booking.cancel.start', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            $cancelled = $this->db->transaction(function () use ($appointment, $correlationId) {
                $appointment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                return $appointment->fresh();
            });

            $this->log->channel('audit')->info('beauty.booking.cancel.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $cancelled->id,
            ]);

            return $cancelled;
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.booking.cancel.error', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить запись и списать расходники.
     */
    public function completeAppointment(
        Appointment $appointment,
        mixed $correlationId = null,
    ): Appointment {
        $correlationId ??= Str::uuid();

        try {
            $this->log->channel('audit')->info('beauty.booking.complete.start', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
            ]);

            $completed = $this->db->transaction(function () use ($appointment, $correlationId) {
                $appointment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Списать расходники если они есть
                // (это будет через InventoryManagementService)

                return $appointment->fresh();
            });

            $this->log->channel('audit')->info('beauty.booking.complete.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $completed->id,
            ]);

            return $completed;
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.booking.complete.error', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить доступные слоты.
     */
    public function getAvailableSlots(
        int $salonId,
        int $masterId,
        int $serviceId,
        string $date,
    ): array {
        try {
            $salon = BeautySalon::findOrFail($salonId);
            
            $workingHours = $salon->working_hours ?? [];
            $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));
            
            if (!isset($workingHours[$dayOfWeek])) {
                return []; 
            }

            $hours = $workingHours[$dayOfWeek];
            $slots = [];

            $current = Carbon::createFromFormat('H:i', $hours['open'] ?? '09:00');
            $end = Carbon::createFromFormat('H:i', $hours['close'] ?? '18:00');

            while ($current < $end) {
                $isOccupied = Appointment::where('master_id', $masterId)
                    ->whereDate('datetime', $date)
                    ->whereTime('datetime', $current->format('H:i'))
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$isOccupied) {
                    $slots[] = [
                        'time' => $current->format('H:i'),
                        'available' => true,
                    ];
                }

                $current->addMinutes(30);
            }

            return $slots;
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.booking.slots.error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
