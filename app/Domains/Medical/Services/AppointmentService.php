<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Domains\Medical\Events\AppointmentBooked;
use App\Domains\Medical\Events\AppointmentCompleted;
use App\Domains\Medical\Models\MedicalAppointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class AppointmentService
{
    public function __construct(
        private readonly \App\Services\FraudControlService $fraudService,
    ) {}

    public function createAppointment(
        int $tenantId,
        int $clinicId,
        int $doctorId,
        int $patientId,
        int $serviceId,
        string $scheduledAt,
        ?string $notes,
        ?string $correlationId = null,
    ): MedicalAppointment {
        $correlationId ??= Str::uuid()->toString();

        try {
            $this->fraudService->check(0, 'create_appointment', 0, null, null, $correlationId);

            return $this->db->transaction(function () use (
                $tenantId,
                $clinicId,
                $doctorId,
                $patientId,
                $serviceId,
                $scheduledAt,
                $notes,
                $correlationId,
            ) {
                $service = \App\Domains\Medical\Models\MedicalService::findOrFail($serviceId);
                $appointment = MedicalAppointment::create([
                    'tenant_id' => $tenantId,
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctorId,
                    'patient_id' => $patientId,
                    'service_id' => $serviceId,
                    'appointment_number' => Str::uuid()->toString(),
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'price' => $service->price,
                    'commission_amount' => $service->price * 0.14,
                    'notes' => $notes,
                    'correlation_id' => $correlationId,
                ]);

                AppointmentBooked::dispatch($appointment, $correlationId);

                $this->log->channel('audit')->info('Medical appointment created', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctorId,
                    'patient_id' => $patientId,
                    'price' => $service->price,
                    'commission_amount' => $appointment->commission_amount,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to create appointment', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function completeAppointment(
        MedicalAppointment $appointment,
        array $diagnosis,
        ?string $correlationId = null,
    ): MedicalAppointment {
        $correlationId ??= Str::uuid()->toString();

        try {
            return $this->db->transaction(function () use ($appointment, $diagnosis, $correlationId) {
                $appointment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'diagnosis' => $diagnosis,
                    'correlation_id' => $correlationId,
                ]);

                AppointmentCompleted::dispatch($appointment, $correlationId);

                $this->log->channel('audit')->info('Medical appointment completed', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to complete appointment', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function cancelAppointment(
        MedicalAppointment $appointment,
        string $reason,
        ?string $correlationId = null,
    ): MedicalAppointment {
        $correlationId ??= Str::uuid()->toString();

        try {
            return $this->db->transaction(function () use ($appointment, $reason, $correlationId) {
                $appointment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'notes' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Medical appointment cancelled', [
                    'appointment_id' => $appointment->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to cancel appointment', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
