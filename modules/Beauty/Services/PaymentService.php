<?php declare(strict_types=1);

namespace Modules\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DomainException;

final class PaymentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private DatabaseManager $database,
            private TinkoffGateway $tinkoff,
            private LoggerInterface $logger,
            private PaymentFraudMLService $fraudMl,
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

            // FRAUD CHECK - ML-based
            try {
                $fraudDto = new PaymentFraudMLDto(
                    tenant_id: $booking->tenant_id,
                    user_id: $booking->user_id,
                    operation_type: 'beauty_payment_init',
                    amount_kopecks: $amount,
                    ip_address: request()->ip() ?? '127.0.0.1',
                    device_fingerprint: request()->header('User-Agent') ?? 'unknown',
                    correlation_id: $correlationId,
                    idempotency_key: 'beauty_payment_' . $booking->id,
                    vertical_code: 'beauty',
                );

                $fraudResult = $this->fraudMl->scorePayment($fraudDto);

                if ($fraudResult['decision'] === 'block') {
                    Log::warning('Beauty payment blocked by fraud detection', [
                        'booking_id' => $booking->id,
                        'fraud_score' => $fraudResult['score'],
                        'explanation' => $fraudResult['explanation'] ?? null,
                        'correlation_id' => $correlationId,
                    ]);
                    throw new DomainException('Payment blocked by fraud detection system');
                }
            } catch (DomainException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::error('Beauty fraud check failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                // Fail-open for reliability
            }

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
