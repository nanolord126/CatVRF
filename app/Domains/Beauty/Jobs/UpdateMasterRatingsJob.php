<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Master;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Services\FraudControlService;

/**
 * UpdateMasterRatingsJob — пересчитывает средний рейтинг всех мастеров
 * на основании отзывов.
 *
 * Запускается ежедневно в 03:30.
 */
final class UpdateMasterRatingsJob implements ShouldQueue
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
        $masters = Master::with('reviews')->get();

        $db->transaction(function () use ($masters, $logger): void {
            foreach ($masters as $master) {
                $oldRating = (float) ($master->rating ?? 0.0);
                $avgRating = round((float) ($master->reviews()->avg('rating') ?? 0.0), 2);

                $master->update([
                    'rating'       => $avgRating,
                    'review_count' => $master->reviews()->count(),
                ]);

                $logger->info('Master rating updated.', [
                    'master_id'      => $master->id,
                    'old_rating'     => $oldRating,
                    'new_rating'     => $avgRating,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });

        $logger->info('UpdateMasterRatingsJob completed.', [
            'masters_count'  => $masters->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:update-master-ratings'];
    }
}
