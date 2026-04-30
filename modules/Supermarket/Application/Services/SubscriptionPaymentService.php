<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Modules\Supermarket\Infrastructure\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SubscriptionPaymentService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    public function chargeNextDelivery(Subscription $subscription, SupermarketOrder $order): bool
    {
        return $this->withSpan(
            'subscription_payment.charge',
            function () use ($subscription, $order) {
                // Fraud check before charging subscription payment
                $this->fraudControl->check([
                    'operation_type' => 'subscription_payment_charge',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'amount' => $order->total_amount,
                    'subscription_id' => $subscription->id,
                    'order_id' => $order->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                return DB::transaction(function () use ($subscription, $order) {
            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'payment_method' => $subscription->is_b2b ? 'invoice' : 'card',
                'attempts' => 1,
            ]);

            try {
                $paymentAdapter = app(\Modules\Payment\Application\Services\PaymentServiceAdapter::class);
                
                if ($subscription->is_b2b) {
                    $result = $this->chargeB2B($subscription, $order);
                } else {
                    $result = $paymentAdapter->chargeRecurring(
                        customerKey: (string) $subscription->buyer_id,
                        amount: $order->total_amount,
                        orderId: (string) $order->id,
                        description: "Подписка #{$subscription->id}"
                    );
                }

                if ($result['success'] ?? false) {
                    $payment->update([
                        'status' => 'success',
                        'gateway_transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                    $order->update(['status' => 'paid']);
                    return true;
                }

                throw new \Exception($result['message'] ?? 'Payment failed');
            } catch (\Exception $e) {
                $this->recordSpanException($e);
                $this->handleFailedPayment($payment, $e->getMessage());
                return false;
            }
            });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_payment_charge',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    private function handleFailedPayment(SubscriptionPayment $payment, string $error): void
    {
        $this->withSpan(
            'subscription_payment.handle_failed',
            function () use ($payment, $error) {
                $payment->increment('attempts');
                $payment->update([
                    'status' => 'failed',
                    'error_message' => $error,
                    'next_attempt_at' => now()->addHours($this->getNextAttemptDelay($payment->attempts)),
                ]);

                if ($payment->attempts >= 3) {
                    $payment->subscription->update(['status' => 'paused']);
                    
                    $notificationService = app(SubscriptionNotificationService::class);
                    $notificationService->sendPaymentFailed(
                        $payment->subscription,
                        $error
                    );
                }

                $this->logAction(
                    action: 'subscription_payment_failed',
                    entityType: 'subscription_payment',
                    entityId: $payment->id,
                    userId: $payment->subscription->buyer_id,
                    context: [
                        'subscription_id' => $payment->subscription_id,
                        'attempt' => $payment->attempts,
                        'error' => $error,
                    ],
                );

                Log::error('Subscription payment failed', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'attempt' => $payment->attempts,
                    'error' => $error,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_payment_handle_failed',
            ),
        );
    }

    private function getNextAttemptDelay(int $attempt): int
    {
        return match ($attempt) {
            1 => 4,
            2 => 24,
            default => 48,
        };
    }

    private function chargeB2B(Subscription $subscription, SupermarketOrder $order): array
    {
        return $this->withSpan(
            'subscription_payment.charge_b2b',
            function () use ($subscription, $order) {
                $gatewayFactory = app(\App\Domains\Shared\Payment\Services\PaymentGatewayFactory::class);
                $gateway = $gatewayFactory->makeByName('tochka');

                return $gateway->createPayment([
                    'amount' => $order->total_amount,
                    'order_id' => $order->id,
                    'description' => "Подписка B2B #{$subscription->id}",
                    'customer' => [
                        'inn' => $subscription->seller->inn ?? null,
                    ],
                    'payment_type' => 'invoice',
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_payment_charge_b2b',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function getPaymentHistory(Subscription $subscription, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return SubscriptionPayment::where('subscription_id', $subscription->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
