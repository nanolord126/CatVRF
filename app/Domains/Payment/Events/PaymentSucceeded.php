<?php

declare(strict_types=1);

namespace App\Domains\Payment\Events;

use App\Domains\Payment\Models\PaymentRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaymentSucceeded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PaymentRecord $payment,
    ) {}
}
