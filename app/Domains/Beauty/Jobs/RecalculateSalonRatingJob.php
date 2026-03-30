<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RecalculateSalonRatingJob extends Model
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
