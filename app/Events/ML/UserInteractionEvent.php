declare(strict_types=1);

namespace App\Events\ML;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: пользователь взаимодействует с товаром/услугой
 * Триггер: просмотр, клик, добавление в корзину, покупка, оценка и т.д.
 *
 * Используется для сбора данных ML-анализа вкусов
 * CANON 2026: User interaction event
 */
final class UserInteractionEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $interactionType,  // product_view, add_to_cart, purchase и т.д.
        public readonly string $interactableType, // Product, Service и т.д.
        public readonly int $interactableId,
        public readonly ?string $vertical = null,
        public readonly ?string $category = null,
        public readonly ?array $itemAttributes = null,  // price, size, color, brand и т.д.
        public readonly ?int $durationSeconds = null,
        public readonly ?array $metadata = null,  // IP, device, source, search_query
        public readonly string $correlationId = '',
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }
}
