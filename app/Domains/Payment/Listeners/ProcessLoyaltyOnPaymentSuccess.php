<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Application\Services\LoyaltyService;
use Modules\Hotels\Infrastructure\Models\BookingModel;
use Modules\Restaurant\Infrastructure\Models\OrderModel;

final class ProcessLoyaltyOnPaymentSuccess
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payable = $event->payment->payable;
        
        if (!$payable) {
            return;
        }

        try {
            if ($payable instanceof BookingModel) {
                $loyaltyProgramId = config('crm-hotels.loyalty_program_id');
                if (!$loyaltyProgramId) {
                    return;
                }
                
                $this->loyaltyService->processBookingLoyalty(
                    $payable->guest_id,
                    $loyaltyProgramId,
                    $event->payment->amount_kopecks / 100,
                    $payable->id,
                );

                Log::channel('audit')->info('Loyalty points awarded for booking', [
                    'booking_id' => $payable->id,
                    'guest_id' => $payable->guest_id,
                    'payment_id' => $event->payment->id,
                    'tenant_id' => $event->payment->tenant_id,
                ]);
            } elseif ($payable instanceof OrderModel) {
                $loyaltyProgramId = config('crm-restaurants.loyalty_program_id');
                if (!$loyaltyProgramId) {
                    return;
                }
                
                $this->loyaltyService->processOrderLoyalty(
                    $payable->user_id,
                    $loyaltyProgramId,
                    $event->payment->amount_kopecks / 100,
                    $payable->id,
                );

                Log::channel('audit')->info('Loyalty points awarded for order', [
                    'order_id' => $payable->id,
                    'user_id' => $payable->user_id,
                    'payment_id' => $event->payment->id,
                    'tenant_id' => $event->payment->tenant_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to process loyalty points', [
                'payment_id' => $event->payment->id,
                'payable_type' => $event->payment->payable_type,
                'payable_id' => $event->payment->payable_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
