<?php

declare(strict_types=1);

namespace App\Notifications\Verticals\RealEstate;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;

final class PropertyListedNotification extends BaseMailableNotification
{
    private readonly string $type = 'realestate.property.listed';

    private readonly string $template = 'emails.realestate.property_listed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your property is now live - '.($data['property_address'] ?? '');
    }
}

final class ViewingConfirmedNotification extends BaseMailableNotification
{
    private readonly string $type = 'realestate.viewing.confirmed';

    private readonly string $template = 'emails.realestate.viewing_confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
        $this->subject = 'Viewing scheduled - '.($data['property_address'] ?? '');
    }
}

final class OfferMadeNotification extends BaseMailableNotification
{
    private readonly string $type = 'realestate.offer.made';

    private readonly string $template = 'emails.realestate.offer_made';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'New offer - ₽'.number_format($data['offer_price'] ?? 0);
    }
}

final class OfferAcceptedNotification extends BaseMailableNotification
{
    private readonly string $type = 'realestate.offer.accepted';

    private readonly string $template = 'emails.realestate.offer_accepted';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Offer accepted! - '.($data['property_address'] ?? '');
    }
}

final class ReviewRequestNotification extends BasePushNotification
{
    private readonly string $type = 'realestate.review.request';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

        $this->title('Rate your experience')
            ->message('Help us improve by rating '.($data['agent_name'] ?? 'your agent'))
            ->type('action')
            ->autoClose(10000)
            ->withAction('Review', '/review/'.($data['transaction_id'] ?? ''), 'primary');
    }
}
