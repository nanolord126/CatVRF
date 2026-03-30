<?php declare(strict_types=1);

namespace App\Notifications\Verticals\RealEstate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PropertyListedNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected string $type = 'realestate.property.listed';
        protected string $template = 'emails.realestate.property_listed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Your property is now live - ' . ($data['property_address'] ?? '');
        }
    }

    final class ViewingConfirmedNotification extends BaseMailableNotification
    {
        protected string $type = 'realestate.viewing.confirmed';
        protected string $template = 'emails.realestate.viewing_confirmed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
            $this->subject = 'Viewing scheduled - ' . ($data['property_address'] ?? '');
        }
    }

    final class OfferMadeNotification extends BaseMailableNotification
    {
        protected string $type = 'realestate.offer.made';
        protected string $template = 'emails.realestate.offer_made';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'New offer - ₽' . number_format($data['offer_price'] ?? 0);
        }
    }

    final class OfferAcceptedNotification extends BaseMailableNotification
    {
        protected string $type = 'realestate.offer.accepted';
        protected string $template = 'emails.realestate.offer_accepted';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Offer accepted! - ' . ($data['property_address'] ?? '');
        }
    }

    final class ReviewRequestNotification extends BasePushNotification
    {
        protected string $type = 'realestate.review.request';

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
