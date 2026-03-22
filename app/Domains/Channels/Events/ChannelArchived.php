<?php declare(strict_types=1);

namespace App\Domains\Channels\Events;

use App\Domains\Channels\Models\BusinessChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Событие: канал архивирован */
final class ChannelArchived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BusinessChannel $channel,
        public readonly string $reason,
        public readonly string $correlationId,
    ) {}
}
