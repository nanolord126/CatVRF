<?php declare(strict_types=1);

namespace App\Domains\Sports\Events;

use App\Domains\Sports\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PurchaseCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Purchase $purchase,
        public string $correlationId = '',
    ) {}
}
