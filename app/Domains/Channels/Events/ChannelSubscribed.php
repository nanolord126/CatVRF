declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Channels\Events;

use App\Domains\Channels\Models\BusinessChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Событие: пользователь подписался на канал */
final class ChannelSubscribed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BusinessChannel $channel,
        public readonly int $userId,
        public readonly string $correlationId,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
