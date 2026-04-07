<?php declare(strict_types=1);

namespace App\Notifications\Verticals\RealEstate;

use Illuminate\Notifications\Notification;

final class PropertyListedNotification extends Model
{

    private string $type = 'realestate.property.listed';
        private string $template = 'emails.realestate.property_listed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Your property is now live - ' . ($data['property_address'] ?? '');
        }
    }

    final class ViewingConfirmedNotification extends BaseMailableNotification
    {
        private string $type = 'realestate.viewing.confirmed';
        private string $template = 'emails.realestate.viewing_confirmed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
            $this->subject = 'Viewing scheduled - ' . ($data['property_address'] ?? '');
        }
    }

    final class OfferMadeNotification extends BaseMailableNotification
    {
        private string $type = 'realestate.offer.made';
        private string $template = 'emails.realestate.offer_made';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'New offer - ₽' . number_format($data['offer_price'] ?? 0);
        }
    }

    final class OfferAcceptedNotification extends BaseMailableNotification
    {
        private string $type = 'realestate.offer.accepted';
        private string $template = 'emails.realestate.offer_accepted';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Offer accepted! - ' . ($data['property_address'] ?? '');
        }
    }

    final class ReviewRequestNotification extends BasePushNotification
    {
        private string $type = 'realestate.review.request';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

            $this->title('Rate your experience')
                 ->message('Help us improve by rating ' . ($data['agent_name'] ?? 'your agent'))
                 ->type('action')
                 ->autoClose(10000)
                 ->withAction('Review', '/review/' . ($data['transaction_id'] ?? ''), 'primary');
        }
}
