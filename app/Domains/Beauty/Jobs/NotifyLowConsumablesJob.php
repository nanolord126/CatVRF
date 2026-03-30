<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NotifyLowConsumablesJob extends Model
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
            $lowStock = Consumable::query()
                ->whereColumn('current_stock', '<=', 'min_stock_threshold')
                ->get();

            foreach ($lowStock as $item) {
                Log::channel('audit')->warning('Low consumable stock', [
                    'consumable_id' => $item->id,
                    'name' => $item->name,
                    'stock' => $item->current_stock,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }
}
