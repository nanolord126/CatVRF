<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Domains\Medical\Models\MedicalDoctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class DoctorService
{
    public function createDoctor(
        int $tenantId,
        int $clinicId,
        int $userId,
        string $fullName,
        string $specialization,
        int $experienceYears,
        ?string $bio,
        ?string $licenseNumber,
        ?string $correlationId = null,
    ): MedicalDoctor {
        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use (
                $tenantId,
                $clinicId,
                $userId,
                $fullName,
                $specialization,
                $experienceYears,
                $bio,
                $licenseNumber,
                $correlationId,
            ) {
                $doctor = MedicalDoctor::create([
                    'tenant_id' => $tenantId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                    'full_name' => $fullName,
                    'specialization' => $specialization,
                    'experience_years' => $experienceYears,
                    'bio' => $bio,
                    'license_number' => $licenseNumber,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Medical doctor created', [
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinicId,
                    'specialization' => $specialization,
                    'correlation_id' => $correlationId,
                ]);

                return $doctor;
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create doctor', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function updateDoctor(
        MedicalDoctor $doctor,
        array $data,
        ?string $correlationId = null,
    ): MedicalDoctor {
        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($doctor, $data, $correlationId) {
                $doctor->update([...$data, 'correlation_id' => $correlationId]);

                Log::channel('audit')->info('Medical doctor updated', [
                    'doctor_id' => $doctor->id,
                    'correlation_id' => $correlationId,
                ]);

                return $doctor;
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update doctor', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
