<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleMasterRatingUpdatedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(MasterRatingUpdated $event): void
        {
            $master = $event->master;

            // Invalidate master rating cache
            Cache::forget("master_rating:{$master->id}");

            // Trigger salon rating recalculation
            if ($master->salon_id) {
                RecalculateSalonRatingJob::dispatch($event->correlationId);
            }

            Log::channel('audit')->info('MasterRatingUpdated event handled', [
                'master_id' => $master->id,
                'old_rating' => $event->oldRating,
                'new_rating' => $event->newRating,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
