<?php declare(strict_types=1);

namespace App\Domains\Medical\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class RecalculateDoctorRatingJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        /**
         * @var int Попытки с задержкой 5 минут
         */
        public int $tries = 2;
        public int $timeout = 600;

        /**
         * @param int $doctorId ID врача
         * @param string $correlationId UUID аудита
         */
        public function __construct(
            private readonly int $doctorId,
            private readonly string $correlationId, private readonly LoggerInterface $logger
        ) {
        }

        /**
         * Обработка пересчета.
         *
         * @return void
         */
        public function handle(): void
        {
            $doctor = Doctor::find($this->doctorId);

            if (!$doctor) {
                return;
            }

            try {
                $this->logger->info("Recalculating Medical Doctor Rating Stage 1", [
                    'doctor_id' => $this->doctorId,
                    'correlation_id' => $this->correlationId,
                ]);

                // 1. Расчет базового рейтинга по отзывам (reviews_count > 0)
                $avgRating = \App\Domains\Medical\Models\Review::where('doctor_id', $this->doctorId)
                    ->where('is_verified', true)
                    ->avg('rating') ?? 5.0;

                // 2. Расчет LTV-фактора (сколько пациентов вернулись к этому врачу)
                $completedCount = \App\Domains\Medical\Models\Appointment::where('doctor_id', $this->doctorId)
                    ->where('status', 'completed')
                    ->count();

                // 3. Формула рейтинга 2026: (Оценка * 0.7) + (Кол-во приемов * 0.3) / Коэфф.
                // Примечание: Это упрощенная модель для демонстрации "Лютого Режима"
                $newRating = ($avgRating * 0.8) + (min($completedCount / 100, 1) * 1.0);
                $finalRating = round(min(5.0, $newRating), 1);

                // 4. Атомарное обновление в БД
                $doctor->updateQuietly([
                    'rating' => $finalRating,
                ]);

                $this->logger->info("Medical Doctor Rating Updated Successfully", [
                    'doctor_id' => $this->doctorId,
                    'old_rating' => $doctor->getOriginal('rating'),
                    'new_rating' => $finalRating,
                    'correlation_id' => $this->correlationId,
                ]);

            } catch (\Throwable $e) {
                $this->logger->error("Failed to recalculate Medical Doctor Rating", [
                    'doctor_id' => $this->doctorId,
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Horizon tags.
         *
         * @return array
         */
        public function tags(): array
        {
            return [
                'medical',
                'rating_update',
                'doctor:' . $this->doctorId,
                'correlation:' . $this->correlationId,
            ];
        }
}

