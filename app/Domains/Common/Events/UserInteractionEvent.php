<?php declare(strict_types=1);

namespace App\Domains\Common\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserInteractionEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public string $correlationId;

        public function __construct(
            public int $userId,
            public int $tenantId,
            public string $interactionType,
            // 'view', 'cart_add', 'cart_remove', 'purchase', 'review', 'rating', 'like', 'wishlist_add'
            public array $data = [],
            // 'product_id', 'vertical', 'category', 'price', 'rating', 'duration_seconds' и т.д.
            public string $ipAddress = '',
            public string $userAgent = '',
        ) {
            $this->correlationId = Str::uuid()->toString();
        }
}
