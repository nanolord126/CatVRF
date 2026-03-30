<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarmAIConstructorResultJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        private int $tries = 3;
        private int $timeout = 60;

        public function __construct(
            private readonly int $userId,
            private readonly string $vertical,
            private readonly array $designData
        ) {}

        public function handle(): void
        {
            try {
                $cacheKey = "ai_constructor:user_{$this->userId}:vertical_{$this->vertical}";
                $cacheTag = "ai_constructor_{$this->userId}";

                $result = [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'design_data' => $this->designData,
                    'cached_at' => now()->toIso8601String(),
                    'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                ];

                Cache::store('redis')
                    ->tags([$cacheTag, "ai_constructor_{$this->vertical}"])
                    ->put($cacheKey, $result, now()->addHours(12));

                Log::channel('audit')->info('AI constructor result cached', [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'correlation_id' => $result['correlation_id'],
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to cache AI constructor result', [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
