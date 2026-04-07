<?php declare(strict_types=1);

namespace App\Notifications\Verticals\TravelSportsFreelancePhotography;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;

/**
 * Class BookingConfirmedNotification
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Notifications\Verticals\TravelSportsFreelancePhotography
 */
final class BookingConfirmedNotification extends BaseMailableNotification
{
    private string $type = 'travel.booking.confirmed';
    private string $template = 'emails.travel.booking_confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your trip is confirmed - Booking #' . ($data['booking_number'] ?? '');
    }
}

final class TravelReminderNotification extends BasePushNotification
{
    private string $type = 'travel.travel.reminder';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

        $this->title('Your trip starts soon!')
             ->body('Depart in ' . ($data['days_until_departure'] ?? '0') . ' days')
             ->type('warning')
             ->priority('high')
             ->autoClose(0);
    }
}

final class ReviewRequestNotification extends BasePushNotification
{
    private string $type = 'photography.review.request';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

        $this->title('Rate your photographer')
             ->message('Help us improve by sharing your experience')
             ->type('action')
             ->withAction('Review', '/photographers/' . ($data['photographer_id'] ?? '') . '/review', 'primary');
    }
}
