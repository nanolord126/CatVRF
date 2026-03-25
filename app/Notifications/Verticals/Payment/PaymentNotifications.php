<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Payment;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;
use App\Notifications\BaseInAppNotification;

/**
 * Payment Initiated Notification - когда платёж создан
 * 
 * Отправляется пользователю когда он инициировал платёж
 * Каналы: Email, Push, In-app
 */
class PaymentInitiatedNotification extends BaseMailableNotification
{
    protected string $type = 'payment.initiated';
    protected string $template = 'emails.payment.initiated';

    /**
     * Конструктор
     */
    public function __construct(int $userId, int $tenantId, array $paymentData)
    {
        parent::__construct(
            $userId,
            $tenantId,
            data: $paymentData,
            channels: ['mail', 'push', 'database']
        );

        $this->subject = 'Ваш платёж ожидает подтверждения';
    }

    /**
     * Получить данные для шаблона
     */
    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'amount' => $this->data['amount'] ?? 0,
            'payment_id' => $this->data['payment_id'] ?? null,
            'description' => $this->data['description'] ?? 'Платёж',
        ]);
    }
}

/**
 * Payment Authorized Notification - когда 3D-Secure пройден
 */
class PaymentAuthorizedNotification extends BaseMailableNotification
{
    protected string $type = 'payment.authorized';
    protected string $template = 'emails.payment.authorized';

    public function __construct(int $userId, int $tenantId, array $paymentData)
    {
        parent::__construct($userId, $tenantId, $paymentData, channels: ['mail', 'database']);
        $this->subject = 'Платёж авторизован';
    }
}

/**
 * Payment Captured Notification - деньги списаны
 */
class PaymentCapturedNotification extends BaseMailableNotification
{
    protected string $type = 'payment.captured';
    protected string $template = 'emails.payment.captured';

    public function __construct(int $userId, int $tenantId, array $paymentData)
    {
        parent::__construct($userId, $tenantId, $paymentData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Платёж успешно выполнен';
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'amount' => $this->data['amount'] ?? 0,
            'wallet_balance_after' => $this->data['wallet_balance_after'] ?? 0,
            'receipt_url' => $this->data['receipt_url'] ?? null,
        ]);
    }
}

/**
 * Payment Failed Notification - платёж не прошёл
 */
class PaymentFailedNotification extends BaseMailableNotification
{
    protected string $type = 'payment.failed';
    protected string $template = 'emails.payment.failed';

    public function __construct(int $userId, int $tenantId, array $paymentData)
    {
        parent::__construct($userId, $tenantId, $paymentData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Платёж не выполнен';
        $this->priority('high');
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'amount' => $this->data['amount'] ?? 0,
            'error_message' => $this->data['error_message'] ?? 'Ошибка платежа',
            'retry_link' => $this->data['retry_link'] ?? null,
        ]);
    }
}

/**
 * Payment Refunded Notification - произведён возврат
 */
class PaymentRefundedNotification extends BaseMailableNotification
{
    protected string $type = 'payment.refunded';
    protected string $template = 'emails.payment.refunded';

    public function __construct(int $userId, int $tenantId, array $paymentData)
    {
        parent::__construct($userId, $tenantId, $paymentData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Ваш возврат одобрен';
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'refund_amount' => $this->data['refund_amount'] ?? 0,
            'refund_reason' => $this->data['refund_reason'] ?? null,
            'days_to_account' => $this->data['days_to_account'] ?? 3,
        ]);
    }
}
