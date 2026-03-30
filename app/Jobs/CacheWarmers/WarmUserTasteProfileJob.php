<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarmUserTasteProfileJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        private int $tries = 3;
        private int $timeout = 30;

        public function __construct(private readonly int $userId) {}

        public function handle(): void
        {
            try {
                $cacheKey = "user_taste_profile_{$this->userId}";
                $cacheTag = "user_taste_{$this->userId}";

                $profile = $this->calculateTasteProfile();

                Cache::store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $profile, now()->addHours(6));

                Log::channel('audit')->info('User taste profile cached', [
                    'user_id' => $this->userId,
                    'correlation_id' => $profile['correlation_id'] ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to warm user taste cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function calculateTasteProfile(): array
        {
            return [
                'user_id' => $this->userId,
                'categories' => [],
                'price_range' => 'mid',
                'preferred_brands' => [],
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'analyzed_at' => now()->toIso8601String(),
            ];
        }
}
