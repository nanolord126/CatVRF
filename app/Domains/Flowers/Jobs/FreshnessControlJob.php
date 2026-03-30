<?php declare(strict_types=1);

namespace App\Domains\Flowers\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreshnessControlJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;

        public function __construct(string $correlationId = null)
        {
            $this->correlationId = $correlationId ?? Str::uuid()->toString();
        }

        /**
         * Выполнение процесса
         */
        public function handle(): void
        {
            Log::channel('audit')->info('FreshnessControlJob started', [
                'correlation_id' => $this->correlationId,
            ]);

            // 1. Получение всех цветов (Inventory) с прошлым сроком свежести
            $staleFlowers = FlowerProduct::whereDate('freshness_date', '<', now())->get();

            foreach ($staleFlowers as $flower) {
                // 2. Списание или уменьшение остатка (как правило, списание в ноль)
                $flower->update([
                    'current_stock' => 0,
                    'status' => 'expired',
                    'correlation_id' => $this->correlationId,
                ]);

                Log::channel('audit')->warning('Flower Expired (Freshness Control)', [
                    'flower_id' => $flower->id,
                    'name' => $flower->name,
                    'freshness_date' => $flower->freshness_date,
                    'correlation_id' => $this->correlationId,
                ]);
            }

            Log::channel('audit')->info('FreshnessControlJob finished', [
                'stale_count' => $staleFlowers->count(),
                'correlation_id' => $this->correlationId,
            ]);
        }
}
