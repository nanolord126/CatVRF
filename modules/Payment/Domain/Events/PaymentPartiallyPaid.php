<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Events;

use Modules\Payment\Domain\Entities\Payment;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class PaymentPartiallyPaid
{
    use Dispatchable;

    public function __construct(
        public Payment $payment,
    ) {}
}
