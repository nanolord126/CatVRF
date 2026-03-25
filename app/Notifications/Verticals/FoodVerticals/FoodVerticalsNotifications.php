<?php declare(strict_types=1);

namespace App\Notifications\Verticals\FoodVerticals;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BaseSmsNotification;
use App\Notifications\BasePushNotification;

// ========== LOGISTICS ==========
final class ShipmentDispatchedNotification extends BasePushNotification
{
    protected string $type = 'logistics.shipment.dispatched';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
        
        $this->title('Your order is on the way!')
             ->body('Tracking: ' . ($data['tracking_number'] ?? ''))
             ->type('info')
             ->deepLink('/shipment/' . ($data['shipment_id'] ?? '') . '/track');
    }
}

final class DeliveryConfirmedNotification extends BasePushNotification
{
    protected string $type = 'logistics.delivery.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Delivered!')
             ->body('Your order has been delivered')
             ->type('success')
             ->autoClose(8000);
    }
}

// ========== FRESH PRODUCE ==========
final class FreshProduceOrderConfirmedNotification extends BaseSmsNotification
{
    protected string $type = 'fresh_produce.order.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

final class FreshProduceDeliveryArrivingNotification extends BasePushNotification
{
    protected string $type = 'fresh_produce.delivery.arriving';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your fresh box is arriving!')
             ->message('Estimated: ' . ($data['estimated_time'] ?? ''))
             ->type('info')
             ->priority('high');
    }
}

// ========== GROCERY ==========
final class GroceryOrderConfirmedNotification extends BaseSmsNotification
{
    protected string $type = 'grocery.order.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

final class GroceryDeliveryArrivingNotification extends BasePushNotification
{
    protected string $type = 'grocery.delivery.arriving';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your groceries are arriving!')
             ->body('Delivery window: ' . ($data['delivery_window'] ?? ''))
             ->type('info')
             ->priority('high')
             ->autoClose(0);
    }
}

// ========== PHARMACY ==========
final class PrescriptionReadyNotification extends BaseSmsNotification
{
    protected string $type = 'pharmacy.prescription.ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

final class MedicineDeliveredNotification extends BaseMailableNotification
{
    protected string $type = 'pharmacy.medicine.delivered';
    protected string $template = 'emails.pharmacy.medicine_delivered';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your medicine has been delivered';
    }
}

// ========== HEALTHY FOOD ==========
final class MealPlanCreatedNotification extends BaseMailableNotification
{
    protected string $type = 'healthy_food.meal_plan.created';
    protected string $template = 'emails.healthy_food.meal_plan_created';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your personalized meal plan is ready';
    }
}

final class DeliveryScheduledNotification extends BasePushNotification
{
    protected string $type = 'healthy_food.delivery.scheduled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Healthy meals scheduled!')
             ->body('First delivery: ' . ($data['delivery_date'] ?? ''))
             ->type('info');
    }
}

// ========== CONFECTIONERY ==========
final class ConfectioneryOrderConfirmedNotification extends BasePushNotification
{
    protected string $type = 'confectionery.order.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your order is being prepared!')
             ->body(($data['items_count'] ?? '0') . ' items in your order')
             ->type('info')
             ->deepLink('/orders/' . ($data['order_id'] ?? ''));
    }
}

final class ConfectioneryReadyForPickupNotification extends BasePushNotification
{
    protected string $type = 'confectionery.ready.pickup';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
        
        $this->title('Your order is ready!')
             ->message('Pick up at ' . ($data['pickup_location'] ?? ''))
             ->type('success')
             ->priority('high')
             ->autoClose(0);
    }
}

// ========== MEAT SHOPS ==========
final class MeatOrderConfirmedNotification extends BasePushNotification
{
    protected string $type = 'meat_shops.order.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Meat box confirmed!')
             ->body('Processing your order...')
             ->type('info');
    }
}

final class MeatOrderReadyNotification extends BaseSmsNotification
{
    protected string $type = 'meat_shops.order.ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

// ========== OFFICE CATERING ==========
final class MenuApprovedNotification extends BaseMailableNotification
{
    protected string $type = 'office_catering.menu.approved';
    protected string $template = 'emails.office_catering.menu_approved';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'database']);
        $this->subject = 'Corporate menu for ' . ($data['delivery_date'] ?? '');
    }
}

final class CateringDeliveryConfirmedNotification extends BasePushNotification
{
    protected string $type = 'office_catering.delivery.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Lunch is arriving!')
             ->body('Delivery: ' . ($data['delivery_time'] ?? ''))
             ->type('info')
             ->priority('high');
    }
}

// ========== FARM DIRECT ==========
final class FarmOrderConfirmedNotification extends BaseMailableNotification
{
    protected string $type = 'farm_direct.order.confirmed';
    protected string $template = 'emails.farm_direct.order_confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Farm box confirmed - from ' . ($data['farm_name'] ?? '');
    }
}

final class FarmPickupTimeNotification extends BaseSmsNotification
{
    protected string $type = 'farm_direct.pickup.time';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

// ========== BOOKS ==========
final class BookOrderShippedNotification extends BaseMailableNotification
{
    protected string $type = 'books.order.shipped';
    protected string $template = 'emails.books.order_shipped';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your book order is on the way';
    }
}

final class BookDeliveryArrivedNotification extends BasePushNotification
{
    protected string $type = 'books.delivery.arrived';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your books have arrived!')
             ->body(($data['book_count'] ?? '0') . ' books ready for pickup')
             ->type('success')
             ->deepLink('/orders/' . ($data['order_id'] ?? ''));
    }
}
