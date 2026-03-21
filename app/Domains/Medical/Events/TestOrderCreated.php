<?php declare(strict_types=1);

namespace App\Domains\Medical\Events;

use App\Domains\Medical\Models\MedicalTestOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TestOrderCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly MedicalTestOrder $testOrder,
        public readonly string $correlationId,
    ) {}
}
