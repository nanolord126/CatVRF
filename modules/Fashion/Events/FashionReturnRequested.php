<?php declare(strict_types=1);

namespace Modules\Fashion\Events;

use App\Domains\Fashion\Models\FashionReturn;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FashionReturnRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionReturn $return,
        public readonly string $correlationId
    ) {}
}
