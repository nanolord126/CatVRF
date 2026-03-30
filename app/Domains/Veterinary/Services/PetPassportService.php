<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetPassportService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Зафиксировать результат вакцинации
         */
        public function addVaccination(int $petId, array $data, string $correlationId): PetVaccination
        {
            return DB::transaction(function () use ($petId, $data, $correlationId) {
                $vaccination = PetVaccination::create([
                    'pet_id' => $petId,
                    'veterinarian_id' => $data['veterinarian_id'] ?? null,
                    'vaccine_name' => $data['vaccine_name'],
                    'serial_number' => $data['serial_number'] ?? null,
                    'vaccinated_at' => $data['vaccinated_at'],
                    'expires_at' => $data['expires_at'],
                    'certificate_url' => $data['certificate_url'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Pet vaccinated', [
                    'pet_id' => $petId,
                    'vaccine' => $data['vaccine_name'],
                    'correlation_id' => $correlationId,
                ]);

                return $vaccination;
            });
        }

        /**
         * Добавить метрику (вес, рост и т.д.)
         */
        public function addMetric(int $petId, string $type, float $value, string $unit, string $correlationId): PetMetric
        {
            return DB::transaction(function () use ($petId, $type, $value, $unit, $correlationId) {
                $metric = PetMetric::create([
                    'pet_id' => $petId,
                    'metric_type' => $type,
                    'value' => $value,
                    'unit' => $unit,
                    'measured_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Если это вес, обновляем основную колонку в модели Pet для кэширования последнего значения
                if ($type === 'weight') {
                    Pet::where('id', $petId)->update(['weight' => $value]);
                }

                Log::channel('audit')->info('Pet metric recorded', [
                    'pet_id' => $petId,
                    'type' => $type,
                    'value' => $value,
                    'correlation_id' => $correlationId,
                ]);

                return $metric;
            });
        }

        /**
         * Создать или обновить родословную
         */
        public function updatePedigree(int $petId, array $data, string $correlationId): PetPedigree
        {
            return DB::transaction(function () use ($petId, $data, $correlationId) {
                $pedigree = PetPedigree::updateOrCreate(
                    ['pet_id' => $petId],
                    array_merge($data, ['correlation_id' => $correlationId])
                );

                Log::channel('audit')->info('Pet pedigree updated', [
                    'pet_id' => $petId,
                    'reg_number' => $data['registration_number'] ?? 'N/A',
                    'correlation_id' => $correlationId,
                ]);

                return $pedigree;
            });
        }

        /**
         * Получить "Дорожную карту" здоровья питомца (Passport Roadmap)
         */
        public function getHealthRoadmap(int $petId): array
        {
            $pet = Pet::with(['vaccinations', 'metrics'])->findOrFail($petId);

            $nextVaccination = $pet->vaccinations()
                ->where('expires_at', '>', now())
                ->orderBy('expires_at', 'asc')
                ->first();

            $weightHistory = $pet->metrics()
                ->where('metric_type', 'weight')
                ->orderBy('measured_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'pet_name' => $pet->name,
                'passport_status' => $pet->passport_number ? 'Verified' : 'Incomplete',
                'chip_status' => $pet->chip_number ? "Installed ({$pet->chip_number})" : 'Not Found',
                'next_vaccination' => $nextVaccination ? $nextVaccination->expires_at->format('d.M.Y') : 'Need check-up',
                'vaccine_due' => $nextVaccination && $nextVaccination->expires_at->diffInDays(now()) < 30,
                'weight_trend' => $this->calculateWeightTrend($weightHistory),
                'last_measured_weight' => $pet->weight,
            ];
        }

        private function calculateWeightTrend(Collection $history): string
        {
            if ($history->count() < 2) return 'stable';

            $latest = $history->first()->value;
            $previous = $history->skip(1)->first()->value;

            if ($latest > $previous * 1.05) return 'increasing';
            if ($latest < $previous * 0.95) return 'decreasing';

            return 'stable';
        }
}
