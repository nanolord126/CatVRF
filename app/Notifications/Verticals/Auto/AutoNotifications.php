<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RideAcceptedNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected string $type = 'auto.ride.accepted';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
        }
    }

    /**
     * DriverArrivingNotification - водитель приближается
     */
    final class DriverArrivingNotification extends BasePushNotification
    {
        protected string $type = 'auto.driver.arriving';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

            $this->title('Your driver is arriving!')
                 ->message('Driver ' . ($data['driver_name'] ?? 'will be here soon'))
                 ->type('info')
                 ->autoClose(0)
                 ->deepLink('/ride/' . ($data['ride_id'] ?? ''));
        }
    }

    /**
     * RideStartedNotification - поездка начата
     */
    final class RideStartedNotification extends BasePushNotification
    {
        protected string $type = 'auto.ride.started';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

            $this->title('Ride started')
                 ->type('info')
                 ->autoClose(8000);
        }
    }

    /**
     * RideCompletedNotification - поездка завершена
     */
    final class RideCompletedNotification extends BaseMailableNotification
    {
        protected string $type = 'auto.ride.completed';
        protected string $template = 'emails.auto.ride_completed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Ride completed - ₽' . ($data['total_cost'] ?? '0');
        }
    }

    /**
     * RideRatingRequestNotification - просим оценить поездку
     */
    final class RideRatingRequestNotification extends BasePushNotification
    {
        protected string $type = 'auto.ride.rating_request';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

            $this->title('Rate your ride')
                 ->message('Help us improve by rating ' . ($data['driver_name'] ?? 'your driver'))
                 ->type('action')
                 ->autoClose(10000)
                 ->withAction('Rate', '/ride/' . ($data['ride_id'] ?? '') . '/rate', 'primary');
        }
    }

    /**
     * ServiceBookingConfirmedNotification - запись в автосервис подтверждена
     */
    final class ServiceBookingConfirmedNotification extends BaseMailableNotification
    {
        protected string $type = 'auto.service_booking.confirmed';
        protected string $template = 'emails.auto.service_booking_confirmed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
            $this->subject = 'Service booking confirmed - ' . ($data['service_name'] ?? 'Auto Service');
        }
    }

    /**
     * ServiceReminderNotification - напоминание о записи
     */
    final class ServiceReminderNotification extends BaseSmsNotification
    {
        protected string $type = 'auto.service.reminder';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push']);
        }
    }

    /**
     * WashBookingConfirmedNotification - запись на мойку подтверждена
     */
    final class WashBookingConfirmedNotification extends BasePushNotification
    {
        protected string $type = 'auto.wash.booking.confirmed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);

            $this->title('Car wash booking confirmed')
                 ->body('Your slot at ' . ($data['wash_location'] ?? '') . ' is reserved');
        }
    }

    /**
     * PayoutProcessedNotification - выплата водителю/автопарку
     */
    final class PayoutProcessedNotification extends BaseMailableNotification
    {
        protected string $type = 'auto.payout.processed';
        protected string $template = 'emails.auto.payout_processed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'database']);
            $this->subject = 'Payout processed - ₽' . ($data['payout_amount'] ?? '0');
        }
}
