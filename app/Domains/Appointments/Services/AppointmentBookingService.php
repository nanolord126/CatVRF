<?php

declare(strict_types=1);

namespace App\Domains\Appointments\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @property string|null $correlationId
 */
final class AppointmentBookingService
{
    private readonly string $correlationId;

    public function __construct(string $correlationId = null)
    {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Создать новую запись с проверкой пересечений и фрода.
     */
    public function createAppointment(array $data): Appointment
    {
        $this->log->channel('audit')->info('Attempting to create appointment', [
            'correlation_id' => $this->correlationId,
            'client_id' => $data['client_id'] ?? null,
            'bookable_type' => $data['bookable_type'] ?? null,
            'bookable_id' => $data['bookable_id'] ?? null,
            'datetime_start' => $data['datetime_start'] ?? null,
        ]);

        // 1. Проверка на фрод
        FraudControlService::check([
            'operation' => 'appointment_booking',
            'client_id' => $data['client_id'],
            'correlation_id' => $this->correlationId,
        ]);

        return $this->db->transaction(function () use ($data) {
            // 2. Проверка пересечений (Overlap detection)
            $start = Carbon::parse($data['datetime_start']);
            $end = Carbon::parse($data['datetime_end']);

            $overlap = Appointment::where('bookable_type', $data['bookable_type'])
                ->where('bookable_id', $data['bookable_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('datetime_start', [$start, $end])
                          ->orWhereBetween('datetime_end', [$start, $end])
                          ->orWhere(function ($q) use ($start, $end) {
                              $q->where('datetime_start', '<=', $start)
                                ->where('datetime_end', '>=', $end);
                          });
                })->exists();

            if ($overlap) {
                $this->log->channel('audit')->warning('Appointment overlap detected', [
                    'correlation_id' => $this->correlationId,
                    'bookable_id' => $data['bookable_id'],
                ]);
                throw new \RuntimeException('Это время уже занято.');
            }

            // 3. Создание записи
            $appointment = Appointment::create(array_merge($data, [
                'correlation_id' => $this->correlationId,
                'tenant_id' => function_exists('tenant') ? tenant('id') : ($data['tenant_id'] ?? 1),
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]));

            $this->log->channel('audit')->info('Appointment created successfully', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $this->correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Подтверждение записи.
     */
    public function confirmAppointment(int $id): bool
    {
        return $this->db->transaction(function () use ($id) {
            $appointment = Appointment::findOrFail($id);
            
            if ($appointment->status !== 'pending') {
                return false;
            }

            $appointment->update([
                'status' => 'confirmed',
                'correlation_id' => $this->correlationId,
            ]);

            $this->log->channel('audit')->info('Appointment confirmed', [
                'appointment_id' => $id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Завершение записи (выполнение услуги).
     */
    public function completeAppointment(int $id): bool
    {
        return $this->db->transaction(function () use ($id) {
            $appointment = Appointment::findOrFail($id);
            
            if ($appointment->status === 'cancelled' || $appointment->status === 'completed') {
                return false;
            }

            $appointment->update([
                'status' => 'completed',
                'correlation_id' => $this->correlationId,
            ]);

            // Здесь в будущем можно вызвать InventoryManagementService::deduct() 
            // для списания расходников на основе типа услуги.

            $this->log->channel('audit')->info('Appointment completed', [
                'appointment_id' => $id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Отмена записи.
     */
    public function cancelAppointment(int $id, string $reason = ''): bool
    {
        return $this->db->transaction(function () use ($id, $reason) {
            $appointment = Appointment::findOrFail($id);
            
            if ($appointment->status === 'completed') {
                throw new \RuntimeException('Нельзя отменить уже завершенную запись.');
            }

            $appointment->update([
                'status' => 'cancelled',
                'notes' => $appointment->notes . "\nПричина отмены: " . $reason,
                'correlation_id' => $this->correlationId,
            ]);

            $this->log->channel('audit')->info('Appointment cancelled', [
                'appointment_id' => $id,
                'reason' => $reason,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }
}
