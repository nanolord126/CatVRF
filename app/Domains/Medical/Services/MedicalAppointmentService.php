<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Medical\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class MedicalAppointmentService
{
    public function __construct()
    {
    }

    /**
     * Создать запись на прием
     */
    public function bookAppointment(
        int $doctorId,
        int $clinicId,
        Carbon $dateTime,
        string $reason,
        string $correlationId,
    ): Appointment {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'bookAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookAppointment', ['domain' => __CLASS__]);

        try {
            $appointment = DB::transaction(function () use ($doctorId, $clinicId, $dateTime, $reason, $correlationId) {
                $appointment = Appointment::create([
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'appointment_date' => $dateTime,
                    'reason' => $reason,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'tenant_id' => tenant()->id,
                ]);

                Log::channel('audit')->info('Medical appointment booked', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });

            return $appointment;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Medical appointment booking failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Подтвердить прием
     */
    public function confirmAppointment(int $appointmentId, string $correlationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'confirmAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL confirmAppointment', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($appointmentId, $correlationId) {
                $appointment = Appointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'confirmed']);

                Log::channel('audit')->info('Medical appointment confirmed', [
                    'appointment_id' => $appointmentId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Medical appointment confirmation failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить прием
     */
    public function completeAppointment(int $appointmentId, string $notes, string $correlationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeAppointment', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($appointmentId, $notes, $correlationId) {
                $appointment = Appointment::findOrFail($appointmentId);
                $appointment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'notes' => $notes,
                ]);

                Log::channel('audit')->info('Medical appointment completed', [
                    'appointment_id' => $appointmentId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Medical appointment completion failed', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
