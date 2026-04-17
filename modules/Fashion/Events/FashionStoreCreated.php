<?php declare(strict_types=1);

namespace Modules\Fashion\Events;

use App\Domains\Fashion\Models\FashionStore;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FashionStoreCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionStore $store,
        public readonly string $correlationId
    ) {}
}
