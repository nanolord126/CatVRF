<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Оставить отзыв после визита.
         */
        public function createReview(Appointment $appointment, array $data, string $correlationId = null): Review
        {
            $correlationId ??= (string) Str::uuid();

            if ($appointment->status !== 'completed') {
                throw new \Exception('Cannot review incomplete appointment');
            }

            return DB::transaction(function () use ($appointment, $data, $correlationId) {
                $review = Review::create(array_merge($data, [
                    'appointment_id' => $appointment->id,
                    'user_id' => $appointment->user_id,
                    'salon_id' => $appointment->salon_id,
                    'master_id' => $appointment->master_id,
                    'uuid' => (string) Str::uuid(),
                    'correlation_id' => $correlationId,
                    'tenant_id' => $appointment->tenant_id,
                ]));

                // Обновление рейтинга мастера/салона (упрощенно)
                $this->recalculateRatings($appointment);

                Log::channel('audit')->info('Review created', [
                    'appointment_id' => $appointment->id,
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });
        }

        /**
         * Пересчитать рейтинг.
         */
        private function recalculateRatings(Appointment $appointment): void
        {
            // Логика пересчета среднего рейтинга в модели BeautySalon и Master
            // Это может быть вынесено в Job
        }
}
