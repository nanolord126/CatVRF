<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;

final class SyncMasterRating implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $masterId,
    ) {
    }

    public function handle(): void
    {
        try {
            // TODO: Implement actual rating calculation from reviews
            // For now, this is a placeholder
            
            $rating = 4.5;
            $totalReviews = 10;

            MasterModel::where('id', $this->masterId)
                ->update([
                    'rating' => $rating,
                    'total_reviews' => $totalReviews,
                ]);

            Log::info('Master rating synced', [
                'master_id' => $this->masterId,
                'rating' => $rating,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to sync master rating', [
                'master_id' => $this->masterId,
                'error' => $e->getMessage(),
            ]);
            $this->release(60);
        }
    }
}
