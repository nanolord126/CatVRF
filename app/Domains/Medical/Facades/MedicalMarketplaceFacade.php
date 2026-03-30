<?php declare(strict_types=1);

namespace App\Domains\Medical\Facades;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalMarketplaceFacade extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
