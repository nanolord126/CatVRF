<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Master;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * UpdateMasterRatingsJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdateMasterRatingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $masters = Master::query()->with('reviews')->get();

        DB::transaction(function () use ($masters): void {
            foreach ($masters as $master) {
                $avgRating = $master->reviews()->avg('rating') ?? 0.0;
                $oldRating = $master->rating;

                $master->update(['rating' => $avgRating]);

                Log::channel('audit')->info('Master rating updated', [
                    'master_id' => $master->id,
                    'old_rating' => $oldRating,
                    'new_rating' => $avgRating,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });
    }
}
