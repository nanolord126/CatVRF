<?php

declare(strict_types=1);

namespace App\Domains\Clinic\Services;

use App\Models\Clinic\MedicalCard;
use App\Models\Clinic\ClinicAppointment;
use App\Models\Clinic\MedicalRecord;
use App\Models\Clinic\Doctor;
use App\Traits\HasAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Throwable;

class ClinicService
{
    use HasAuditLog;

    /**
     * Создать медицинскую карточку пациента
     */
    public function createMedicalCard(array $data): MedicalCard
    {
        return DB::transaction(function () use ($data): MedicalCard {
            try {
                $medicalCard = MedicalCard::create([
                    'user_id' => $data['user_id'],
                    'tenant_id' => $data['tenant_id'],
                    'clinic_id' => $data['clinic_id'] ?? null,
                    'blood_type' => $data['blood_type'] ?? null,
                    'medical_history' => $data['medical_history'] ?? null,
                    'allergies' => $data['allergies'] ?? null,
                    'active_medications' => $data['active_medications'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->createAuditLog(
                    'medical_cards',
                    $medicalCard->id,
                    'create',
                    [],
                    $medicalCard->toArray(),
                    $data['correlation_id'] ?? null
                );

                Log::info('Clinic: Medical card created', [
                    'medical_card_id' => $medicalCard->id,
                    'user_id' => $medicalCard->user_id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $medicalCard;
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to create medical card', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Обновить медицинскую карточку
     */
    public function updateMedicalCard(MedicalCard $medicalCard, array $data): MedicalCard
    {
        return DB::transaction(function () use ($medicalCard, $data): MedicalCard {
            try {
                $oldData = $medicalCard->toArray();

                $medicalCard->update([
                    'blood_type' => $data['blood_type'] ?? $medicalCard->blood_type,
                    'medical_history' => $data['medical_history'] ?? $medicalCard->medical_history,
                    'allergies' => $data['allergies'] ?? $medicalCard->allergies,
                    'active_medications' => $data['active_medications'] ?? $medicalCard->active_medications,
                    'notes' => $data['notes'] ?? $medicalCard->notes,
                ]);

                $this->createAuditLog(
                    'medical_cards',
                    $medicalCard->id,
                    'update',
                    $oldData,
                    $medicalCard->toArray(),
                    $data['correlation_id'] ?? null
                );

                Log::info('Clinic: Medical card updated', [
                    'medical_card_id' => $medicalCard->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $medicalCard->fresh();
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to update medical card', [
                    'medical_card_id' => $medicalCard->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Создать запись в медицинской карточке
     */
    public function addMedicalRecord(MedicalCard $medicalCard, array $data): MedicalRecord
    {
        return DB::transaction(function () use ($medicalCard, $data): MedicalRecord {
            try {
                $record = MedicalRecord::create([
                    'medical_card_id' => $medicalCard->id,
                    'doctor_id' => $data['doctor_id'],
                    'record_type' => $data['record_type'], // diagnosis, prescription, lab_result, note
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'recorded_at' => $data['recorded_at'] ?? now(),
                ]);

                $this->createAuditLog(
                    'medical_records',
                    $record->id,
                    'create',
                    [],
                    $record->toArray(),
                    $data['correlation_id'] ?? null
                );

                Log::info('Clinic: Medical record added', [
                    'record_id' => $record->id,
                    'medical_card_id' => $medicalCard->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $record;
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to add medical record', [
                    'medical_card_id' => $medicalCard->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Создать запись на прием к врачу
     */
    public function createAppointment(array $data): ClinicAppointment
    {
        return DB::transaction(function () use ($data): ClinicAppointment {
            try {
                // Проверка доступности врача
                $isAvailable = $this->isDoctorAvailable(
                    $data['doctor_id'],
                    Carbon::parse($data['appointment_date'])
                );

                if (!$isAvailable) {
                    throw new \Exception('Врач недоступен в это время');
                }

                $appointment = ClinicAppointment::create([
                    'medical_card_id' => $data['medical_card_id'],
                    'doctor_id' => $data['doctor_id'],
                    'appointment_date' => $data['appointment_date'],
                    'appointment_type' => $data['appointment_type'] ?? 'consultation',
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $this->createAuditLog(
                    'clinic_appointments',
                    $appointment->id,
                    'create',
                    [],
                    $appointment->toArray(),
                    $data['correlation_id'] ?? null
                );

                Log::info('Clinic: Appointment created', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $appointment;
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to create appointment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Подтвердить запись на прием
     */
    public function confirmAppointment(ClinicAppointment $appointment): ClinicAppointment
    {
        return DB::transaction(function () use ($appointment): ClinicAppointment {
            try {
                $oldData = $appointment->toArray();

                $appointment->update(['status' => 'confirmed']);

                $this->createAuditLog(
                    'clinic_appointments',
                    $appointment->id,
                    'update',
                    $oldData,
                    $appointment->toArray(),
                    $appointment->correlation_id ?? null
                );

                Log::info('Clinic: Appointment confirmed', [
                    'appointment_id' => $appointment->id,
                ]);

                return $appointment->fresh();
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to confirm appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Выполнить прием пациента
     */
    public function completeAppointment(ClinicAppointment $appointment, array $data): ClinicAppointment
    {
        return DB::transaction(function () use ($appointment, $data): ClinicAppointment {
            try {
                $oldData = $appointment->toArray();

                $appointment->update([
                    'status' => 'completed',
                    'completion_notes' => $data['completion_notes'] ?? null,
                ]);

                // Если есть диагноз, добавить в медицинскую карточку
                if (!empty($data['diagnosis'])) {
                    $this->addMedicalRecord($appointment->medicalCard, [
                        'doctor_id' => $appointment->doctor_id,
                        'record_type' => 'diagnosis',
                        'title' => 'Диагноз из приема',
                        'content' => $data['diagnosis'],
                        'recorded_at' => now(),
                        'correlation_id' => $data['correlation_id'] ?? null,
                    ]);
                }

                $this->createAuditLog(
                    'clinic_appointments',
                    $appointment->id,
                    'update',
                    $oldData,
                    $appointment->toArray(),
                    $data['correlation_id'] ?? null
                );

                Log::info('Clinic: Appointment completed', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $appointment->fresh();
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to complete appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Отменить запись на прием
     */
    public function cancelAppointment(ClinicAppointment $appointment, string $reason): ClinicAppointment
    {
        return DB::transaction(function () use ($appointment, $reason): ClinicAppointment {
            try {
                $oldData = $appointment->toArray();

                $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);

                $this->createAuditLog(
                    'clinic_appointments',
                    $appointment->id,
                    'delete',
                    $oldData,
                    $appointment->toArray(),
                    null
                );

                Log::info('Clinic: Appointment cancelled', [
                    'appointment_id' => $appointment->id,
                    'reason' => $reason,
                ]);

                return $appointment->fresh();
            } catch (Throwable $e) {
                Log::error('Clinic: Failed to cancel appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Проверить доступность врача
     */
    private function isDoctorAvailable(int $doctorId, Carbon $appointmentDate): bool
    {
        try {
            $cacheKey = "doctor_availability_{$doctorId}_{$appointmentDate->format('Y-m-d')}";

            return Cache::remember($cacheKey, 3600, function () use ($doctorId, $appointmentDate) {
                $conflictingAppointments = ClinicAppointment::where('doctor_id', $doctorId)
                    ->where('appointment_date', $appointmentDate->format('Y-m-d'))
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                // Если более 5 приемов в день - врач перегружен
                return $conflictingAppointments < 5;
            });
        } catch (Throwable $e) {
            Log::error('Clinic: Failed to check doctor availability', [
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить доступных врачей для специальности
     */
    public function getAvailableDoctors(string $specialty, Carbon $fromDate): Collection
    {
        try {
            return Cache::remember(
                "available_doctors_{$specialty}_{$fromDate->format('Y-m-d')}",
                3600,
                function () use ($specialty) {
                    return Doctor::query()
                        ->where('specialty', $specialty)
                        ->where('is_active', true)
                        ->with('schedule')
                        ->get();
                }
            );
        } catch (Throwable $e) {
            Log::error('Clinic: Failed to get available doctors', [
                'specialty' => $specialty,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Получить статистику клиники
     */
    public function getClinicStatistics(int $clinicId, int $daysAgo = 30): array
    {
        try {
            $fromDate = now()->subDays($daysAgo);

            $totalAppointments = ClinicAppointment::whereHas('medicalCard.user', function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            })
                ->where('created_at', '>=', $fromDate)
                ->count();

            $completedAppointments = ClinicAppointment::whereHas('medicalCard.user', function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            })
                ->where('status', 'completed')
                ->where('created_at', '>=', $fromDate)
                ->count();

            $cancelledAppointments = ClinicAppointment::whereHas('medicalCard.user', function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            })
                ->where('status', 'cancelled')
                ->where('created_at', '>=', $fromDate)
                ->count();

            $totalPatients = MedicalCard::where('clinic_id', $clinicId)->count();

            $averageAppointmentsPerPatient = $totalPatients > 0 ? $totalAppointments / $totalPatients : 0;

            return [
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'cancelled_appointments' => $cancelledAppointments,
                'completion_rate' => $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 2) : 0,
                'total_patients' => $totalPatients,
                'average_appointments_per_patient' => round($averageAppointmentsPerPatient, 2),
                'period_days' => $daysAgo,
            ];
        } catch (Throwable $e) {
            Log::error('Clinic: Failed to get statistics', [
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
