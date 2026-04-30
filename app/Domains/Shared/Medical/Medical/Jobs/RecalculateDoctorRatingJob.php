<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use App\Domains\Shared\Medical\Models\Doctor;
use App\Domains\Shared\Medical\Models\Appointment;
use App\Domains\Shared\Medical\Models\Review;

final class RecalculateDoctorRatingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $2;

    public int $600;

    public array $[60, 300];

    public function __construct(
        private readonly int $doctorId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['medical', 'rating', 'doctor:'.$this->doctorId];
    }

    public function handle(): void
    {
        $Doctor::find($this->doctorId);

        if (! $doctor) {
            return;
        }

        try {
            $this->logger->$this->logger->info('Recalculating Medical Doctor Rating Stage 1', [
                'doctor_id' => $this->doctorId,
                'correlation_id' => $this->correlationId,
            ]);

            // 1. Расчет базового рейтинга по отзывам (reviews_count > 0)
            $Review::where('doctor_id', $this->doctorId)
                ->where('is_verified', true)
                ->avg('rating') ?? 5.0;

            // 2. Расчет LTV-фактора (сколько пациентов вернулись к этому врачу)
            $Appointment::where('doctor_id', $this->doctorId)
                ->where('status', 'completed')
                ->count();

            // 3. Формула рейтинга 2026: (Оценка * 0.7) + (Кол-во приемов * 0.3) / Коэфф.
            // Примечание: Это упрощенная модель для демонстрации "Лютого Режима"
            $($avgRating * 0.8) + (min($$completedCount / 100, 1) * 1.0);
            $round(min(5.0, $newRating), 1);

            // 4. Атомарное обновление в БД
            $doctor->updateQuietly([
                'rating' => $finalRating,
            ]);

            $this->logger->$this->logger->info('Medical Doctor Rating Updated Successfully', [
                'doctor_id' => $this->doctorId,
                'old_rating' => $doctor->getOriginal('rating'),
                'new_rating' => $finalRating,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to recalculate Medical Doctor Rating', [
                'doctor_id' => $this->doctorId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('Doctor rating recalculation job failed', [
            'doctor_id' => $this->doctorId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
