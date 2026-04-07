<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Services\FraudControlService;

/**
 * RecalculateSalonRatingJob — пересчитывает средний рейтинг всех салонов
 * на основании отзывов.
 *
 * Запускается ежедневно в 03:00.
 */
final class RecalculateSalonRatingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(
        LoggerInterface $logger,
        \Illuminate\Database\DatabaseManager $db,
    ): void {
        $salons = BeautySalon::with('reviews')->get();

        $db->transaction(function () use ($salons, $logger): void {
            foreach ($salons as $salon) {
                $avgRating = round((float) ($salon->reviews()->avg('rating') ?? 0.0), 2);
                $salon->update([
                    'rating'       => $avgRating,
                    'review_count' => $salon->reviews()->count(),
                ]);

                $logger->info('Salon rating updated.', [
                    'salon_id'       => $salon->id,
                    'rating'         => $avgRating,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });

        $logger->info('RecalculateSalonRatingJob completed.', [
            'salons_count'   => $salons->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:recalculate-salon-rating'];
    }
}
