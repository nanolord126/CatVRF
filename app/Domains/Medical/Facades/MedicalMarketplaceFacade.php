<?php

declare(strict_types=1);

namespace App\Domains\Medical\Facades;

use App\Domains\Medical\Models\MedicalClinic;
use App\Domains\Medical\Models\MedicalDoctor;
use App\Domains\Medical\Models\MedicalService;
use Illuminate\Support\Collection;

/**
 * КАНОН 2026 — MEDICAL MARKETPLACE FACADE
 * Слой 5: Публичная витрина (B2C)
 */
final readonly class MedicalMarketplaceFacade
{
    /**
     * Поиск клиник по специализации и гео
     */
    public function searchClinics(array $filters): Collection
    {
        $query = MedicalClinic::query()->where('is_active', true);

        if (!empty($filters['specialization'])) {
            $query->whereJsonContains('specializations', $filters['specialization']);
        }

        if (!empty($filters['verified_only'])) {
            $query->where('is_verified', true);
        }

        return $query->orderByDesc('rating')->limit(20)->get();
    }

    /**
     * Получение профиля врача со свободными слотами
     */
    public function getDoctorProfile(string $uuid): ?MedicalDoctor
    {
        return MedicalDoctor::where('uuid', $uuid)
            ->with(['clinic', 'appointments' => fn($q) => $q->where('starts_at', '>', now())])
            ->first();
    }

    /**
     * Список услуг клиники для выбора пользователем
     */
    public function getClinicServices(int $clinicId): Collection
    {
        return MedicalService::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get();
    }
}
