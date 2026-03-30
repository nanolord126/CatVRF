<?php declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentEventListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly NotificationService $notificationService,
        ) {}

        public function handlePaymentInitiated(PaymentInitiatedEvent $event): void
        {
            try {
                $user = User::find($event->userId);
                if (!$user) {
                    return;
                }

                $notification = new PaymentInitiatedNotification(
                    $event->userId,
                    $event->tenantId,
                    [
                        'payment_id' => $event->paymentId,
                        'amount' => $event->amount,
                        'currency' => 'RUB',
                        'payment_method' => $event->paymentMethod,
                        'description' => $event->description,
                    ]
                );

                $this->notificationService->send($user, $notification, $event->correlationId);
            } catch (\Throwable $e) {
                Log::channel('notifications')
                    ->error('Failed to send PaymentInitiatedNotification', [
                        'event' => PaymentInitiatedEvent::class,
                        'error' => $e->getMessage(),
                        'correlation_id' => $event->correlationId,
                    ]);
            }
        }

        public function handlePaymentAuthorized(PaymentAuthorizedEvent $event): void
        {
            try {
                $user = User::find($event->userId);
                if (!$user) {
                    return;
                }

                $notification = new PaymentAuthorizedNotification(
                    $event->userId,
                    $event->tenantId,
                    [
                        'payment_id' => $event->paymentId,
                        'amount' => $event->amount,
                        'currency' => 'RUB',
                    ]
                );

                $this->notificationService->send($user, $notification, $event->correlationId);
            } catch (\Throwable $e) {
                Log::channel('notifications')
                    ->error('Failed to send PaymentAuthorizedNotification', [
                        'error' => $e->getMessage(),
                        'correlation_id' => $event->correlationId,
                    ]);
            }
        }

        public function handlePaymentCaptured(PaymentCapturedEvent $event): void
        {
            try {
                $user = User::find($event->userId);
                if (!$user) {
                    return;
                }

                $notification = new PaymentCapturedNotification(
                    $event->userId,
                    $event->tenantId,
                    [
                        'payment_id' => $event->paymentId,
                        'amount' => $event->amount,
                        'currency' => 'RUB',
                        'transaction_id' => $event->transactionId,
                        'receipt_url' => $event->receiptUrl,
                        'wallet_balance_after' => 0, // Заполнится из сервиса
                    ]
                );

                $this->notificationService->send($user, $notification, $event->correlationId);
            } catch (\Throwable $e) {
                Log::channel('notifications')
                    ->error('Failed to send PaymentCapturedNotification', [
                        'error' => $e->getMessage(),
                        'correlation_id' => $event->correlationId,
                    ]);
            }
        }

        public function handlePaymentFailed(PaymentFailedEvent $event): void
        {
            try {
                $user = User::find($event->userId);
                if (!$user) {
                    return;
                }

                $notification = new PaymentFailedNotification(
                    $event->userId,
                    $event->tenantId,
                    [
                        'payment_id' => $event->paymentId,
                        'error_code' => $event->errorCode,
                        'error_message' => $event->errorMessage,
                        'can_retry' => $event->canRetry,
                        'retry_link' => $event->canRetry ? '/payments/' . $event->paymentId . '/retry' : null,
                    ]
                );

                $this->notificationService->send($user, $notification, $event->correlationId);
            } catch (\Throwable $e) {
                Log::channel('notifications')
                    ->error('Failed to send PaymentFailedNotification', [
                        'error' => $e->getMessage(),
                        'correlation_id' => $event->correlationId,
                    ]);
            }
        }

        public function handlePaymentRefunded(PaymentRefundedEvent $event): void
        {
            try {
                $user = User::find($event->userId);
                if (!$user) {
                    return;
                }

                $notification = new PaymentRefundedNotification(
                    $event->userId,
                    $event->tenantId,
                    [
                        'payment_id' => $event->paymentId,
                        'refund_amount' => $event->refundAmount,
                        'currency' => 'RUB',
                        'refund_reason' => $event->refundReason,
                        'days_to_account' => $event->daysToAccount,
                    ]
                );

                $this->notificationService->send($user, $notification, $event->correlationId);
            } catch (\Throwable $e) {
                Log::channel('notifications')
                    ->error('Failed to send PaymentRefundedNotification', [
                        'error' => $e->getMessage(),
                        'correlation_id' => $event->correlationId,
                    ]);
            }
        }
}
