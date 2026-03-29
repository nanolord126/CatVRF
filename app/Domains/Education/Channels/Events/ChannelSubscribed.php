<?php

declare(strict_types=1);


namespace App\Domains\Content\Channels\Events;

use App\Domains\Education\Channels\Models\BusinessChannel;
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
    ) {}
}
