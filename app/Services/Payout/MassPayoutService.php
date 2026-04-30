<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Domains\Finances\Services\PayoutService;
use Illuminate\Support\Collection;

final readonly class MassPayoutService
{
    public function __construct(
        private PayoutService $payoutService,
    ) {}

    public function processBatch(Collection $payouts): array
    {
        return $payouts->map(fn ($payout) => $this->payoutService->initiate($payout))->toArray();
    }
}
