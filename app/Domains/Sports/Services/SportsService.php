<?php

namespace App\Domains\Sports\Services;

use App\Domains\Sports\Models\Gym;
use App\Domains\Finances\Services\PaymentService;
use App\Domains\Finances\Services\WalletService;

class SportsService
{
    public function __construct(
        private PaymentService $paymentService,
        private WalletService $walletService
    ) {}

    public function bookSession(Gym $gym, array $data): array
    {
        // 1. Log session
        $booking = [
            'gym_id' => $gym->id,
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'correlation_id' => bin2hex(random_bytes(16)),
        ];

        // 2. Process via Wallet or External
        if ($data['payment_method'] === 'wallet') {
            $this->walletService->debit($data['user'], $data['amount'], "Booking: {$gym->name}");
            return ['status' => 'confirmed', 'booking' => $booking];
        }

        // 3. Initiate External
        return $this->paymentService->initPayment([
            'amount' => $data['amount'],
            'order_id' => "GYM-{$gym->id}-" . time(),
            'user_id' => $data['user_id']
        ]);
    }
}
