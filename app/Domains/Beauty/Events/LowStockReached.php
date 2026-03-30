<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockReached extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            readonly public BeautyProduct $product,
            readonly public string $correlationId = '',
        ) {
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('beauty.inventory'),
            ];
        }
}
