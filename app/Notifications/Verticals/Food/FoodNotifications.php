<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Food;

use App\Notifications\BaseInAppNotification;
use App\Notifications\BaseMailableNotification;
use App\Notifications\BaseSmsNotification;

/**
 * Order Confirmed Notification - заказ подтверждён рестораном
 */
final class OrderConfirmedNotification extends BaseSmsNotification
{
    private string $type = 'food.order.confirmed';
    private string $template = 'sms.food.confirmed';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['sms', 'push', 'database']);
    }

    public function getSmsText(): string
    {
        $restaurantName = $this->data['restaurant_name'] ?? 'Ресторан';
        $estimatedTime = $this->data['estimated_time_minutes'] ?? 30;
        return "Заказ #{$this->data['order_id']} принят! Приготовление: ~$estimatedTime мин. $restaurantName";
    }
}

/**
 * Order Ready Notification - заказ готов к выдаче/доставке
 */
final class OrderReadyNotification extends BaseInAppNotification
{
    private string $type = 'food.order.ready';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['push', 'database']);

        $this->title('Ваш заказ готов!')
             ->message("{$orderData['restaurant_name']}: Заказ #{$orderData['order_id']} готов к получению")
             ->type('success')
             ->autoClose(0) // Не закрывать автоматически
             ->withAction('Перейти в ресторан', '/restaurant/' . ($orderData['restaurant_id'] ?? ''));
    }
}

/**
 * Order Delivering Notification - заказ в пути
 */
final class OrderDeliveringNotification extends BaseInAppNotification
{
    private string $type = 'food.order.delivering';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['push', 'database']);

        $this->title('Курьер едет к вам')
             ->message("Курьер #{$orderData['courier_id']} доставит ваш заказ")
             ->type('info')
             ->withAction('Отследить доставку', '/order/' . ($orderData['order_id'] ?? '') . '/tracking');
    }
}

/**
 * Order Delivered Notification - заказ доставлен
 */
final class OrderDeliveredNotification extends BaseMailableNotification
{
    private string $type = 'food.order.delivered';
    private string $template = 'emails.food.order_delivered';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Ваш заказ доставлен';
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'restaurant_name' => $this->data['restaurant_name'] ?? null,
            'order_total' => $this->data['order_total'] ?? 0,
            'delivery_cost' => $this->data['delivery_cost'] ?? 0,
            'receipt_url' => $this->data['receipt_url'] ?? null,
        ]);
    }
}

/**
 * Order Cancelled Notification - заказ отменён
 */
final class OrderCancelledNotification extends BaseMailableNotification
{
    private string $type = 'food.order.cancelled';
    private string $template = 'emails.food.order_cancelled';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Ваш заказ отменён';
        $this->priority('high');
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'restaurant_name' => $this->data['restaurant_name'] ?? null,
            'cancellation_reason' => $this->data['cancellation_reason'] ?? null,
            'refund_amount' => $this->data['refund_amount'] ?? 0,
        ]);
    }
}

/**
 * Rating Request - попросить оценку блюда
 */
final class RatingRequestNotification extends BaseInAppNotification
{
    private string $type = 'food.rating_request';

    public function __construct(int $userId, int $tenantId, array $orderData)
    {
        parent::__construct($userId, $tenantId, $orderData, channels: ['database', 'push']);

        $this->title('Как вам заказ?')
             ->message("Оцените блюда из {$orderData['restaurant_name']}")
             ->type('info')
             ->autoClose(8000)
             ->withAction('Оставить отзыв', '/order/' . ($orderData['order_id'] ?? '') . '/rate');
    }
}

/**
 * Special Offer - спецпредложение от ресторана
 */
final class SpecialOfferNotification extends BaseMailableNotification
{
    private string $type = 'food.special_offer';
    private string $template = 'emails.food.special_offer';

    public function __construct(int $userId, int $tenantId, array $offerData)
    {
        parent::__construct($userId, $tenantId, $offerData, channels: ['mail', 'push', 'database']);
        $this->subject = "Спецпредложение от {$offerData['restaurant_name']}!";
    }
}
