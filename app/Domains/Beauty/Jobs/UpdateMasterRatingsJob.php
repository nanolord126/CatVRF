<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateMasterRatingsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
