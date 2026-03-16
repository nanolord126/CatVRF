<?php

namespace Modules\Beauty\Services;

use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Beauty\Enums\PaymentStatus;
use Modules\Beauty\Models\Booking;
use Modules\Beauty\Models\Payment;
use Modules\Payments\Gateways\TinkoffGateway;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private DatabaseManager $database,
        private TinkoffGateway $tinkoff,
        private LoggerInterface $logger,
    ) {}

    public function initiatePayment(Booking $booking): array
    {
        if (!$booking->canBePaid()) {
            throw new Exception('Бронирование не может быть оплачено в текущем статусе');
        }

        $service = $booking->service;
        if (!$service) {
            throw new Exception('Услуга не найдена');
        }

        $correlationId = Str::uuid();
        $amount = (int)($service->price * 100); // В копейках для Tinkoff

        try {
            $payment = $this->database->transaction(function () use (
                $booking,
                $service,
                $correlationId,
            ) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'salon_id' => $booking->salon_id,
                    'tenant_id' => $booking->tenant_id,
                    'amount' => $service->price,
                    'status' => PaymentStatus::PENDING,
                    'payment_method' => 'tinkoff',
                    'commission_percent' => 20.00,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Payment initiated', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                    'amount' => $service->price,
                ]);

                return $payment;
            });

            // Создать платёж в Tinkoff
            $paymentUrl = $this->tinkoff->createPayment(
                paymentId: $payment->id,
                amount: $amount,
                orderId: 'BOOKING_' . $booking->id,
                description: 'Бронирование услуги: ' . $service->name,
                redirectUrl: route('beauty.payment.success', ['payment' => $payment->id]),
                failUrl: route('beauty.payment.failed', ['payment' => $payment->id]),
            );

            return [
                'payment_id' => $payment->id,
                'payment_url' => $paymentUrl,
                'correlation_id' => $correlationId,
                'amount' => $service->price,
            ];
        } catch (Exception $e) {
            $this->logger->error('Failed to initiate payment', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function confirmPayment(Payment $payment, string $tinkoffPaymentId): Payment
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new Exception('Платёж может быть подтверждён только из статуса "Ожидание"');
        }

        return $this->database->transaction(function () use ($payment, $tinkoffPaymentId) {
            $salonPayout = $payment->amount * 0.8; // 80% салону
            $platformCommission = $payment->amount * 0.2; // 20% платформе

            $payment->update([
                'tinkoff_payment_id' => $tinkoffPaymentId,
                'salon_payout_amount' => $salonPayout,
                'platform_commission_amount' => $platformCommission,
            ]);

            $payment->markAsConfirmed();

            // Зачислить средства в wallet салона
            $salon = $payment->salon;
            if ($salon) {
                $salon->wallet()->deposit($salonPayout, [
                    'correlation_id' => $payment->correlation_id,
                    'booking_id' => $payment->booking_id,
                    'reason' => 'Beauty booking payment',
                ]);

                $this->logger->info('Salon wallet deposited', [
                    'salon_id' => $salon->id,
                    'amount' => $salonPayout,
                    'correlation_id' => $payment->correlation_id,
                ]);
            }

            $this->logger->info('Payment confirmed', [
                'payment_id' => $payment->id,
                'correlation_id' => $payment->correlation_id,
                'salon_payout' => $salonPayout,
                'platform_commission' => $platformCommission,
            ]);

            return $payment->fresh();
        });
    }

    public function failPayment(Payment $payment, string $reason = ''): Payment
    {
        return $this->database->transaction(function () use ($payment, $reason) {
            $payment->markAsFailed();

            $this->logger->warning('Payment failed', [
                'payment_id' => $payment->id,
                'correlation_id' => $payment->correlation_id,
                'reason' => $reason,
            ]);

            return $payment;
        });
    }

    public function refundPayment(Payment $payment, string $reason = ''): Payment
    {
        if (!$payment->isConfirmed()) {
            throw new Exception('Возврат возможен только для подтвёржденных платежей');
        }

        return $this->database->transaction(function () use ($payment, $reason) {
            // Вернуть средства из wallet салона
            $salon = $payment->salon;
            if ($salon && $payment->salon_payout_amount) {
                $salon->wallet()->forceWithdraw(
                    $payment->salon_payout_amount,
                    [
                        'correlation_id' => $payment->correlation_id,
                        'reason' => 'Refund: ' . $reason,
                    ]
                );
            }

            // Отправить запрос на возврат в Tinkoff
            if ($payment->tinkoff_payment_id) {
                $this->tinkoff->refund($payment->tinkoff_payment_id);
            }

            $payment->markAsRefunded();

            $this->logger->info('Payment refunded', [
                'payment_id' => $payment->id,
                'correlation_id' => $payment->correlation_id,
                'reason' => $reason,
            ]);

            return $payment;
        });
    }
}
