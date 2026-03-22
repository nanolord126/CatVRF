<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RecalculateSalonRatingJob implements ShouldQueue
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
        $salons = BeautySalon::query()->with('reviews')->get();

        DB::transaction(function () use ($salons): void {
            foreach ($salons as $salon) {
                $avgRating = $salon->reviews()->avg('rating') ?? 0.0;
                $salon->update(['rating' => $avgRating]);

                Log::channel('audit')->info('Salon rating updated', [
                    'salon_id' => $salon->id,
                    'rating' => $avgRating,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });
    }
}
