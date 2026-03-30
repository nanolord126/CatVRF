<?php declare(strict_types=1);

namespace App\Notifications\Verticals\TravelSportsFreelancePhotography;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;

final class BookingConfirmedNotification extends BaseMailableNotification
{
    protected string $type = 'travel.booking.confirmed';
    protected string $template = 'emails.travel.booking_confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your trip is confirmed - Booking #' . ($data['booking_number'] ?? '');
    }
}

final class TravelReminderNotification extends BasePushNotification
{
    protected string $type = 'travel.travel.reminder';

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
    protected string $type = 'travel.review.request';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

        $this->title('Share your experience')
             ->message('Rate your trip to ' . ($data['destination'] ?? ''))
             ->type('action')
             ->autoClose(10000)
             ->withAction('Review', '/bookings/' . ($data['booking_id'] ?? '') . '/review', 'primary');
    }
}

namespace App\Notifications\Verticals\TravelSportsFreelancePhotography;

final class MembershipRenewedNotification extends BaseMailableNotification
{
    protected string $type = 'sports.membership.renewed';
    protected string $template = 'emails.sports.membership_renewed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Membership renewed until ' . ($data['expires_at'] ?? '');
    }
}

final class ClassBookedNotification extends BasePushNotification
{
    protected string $type = 'sports.class.booked';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

        $this->title('Class booked!')
             ->body(($data['class_name'] ?? '') . ' at ' . ($data['class_time'] ?? ''))
             ->type('success')
             ->deepLink('/classes/' . ($data['class_id'] ?? '']);
    }
}

final class ClassReminderNotification extends BasePushNotification
{
    protected string $type = 'sports.class.reminder';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

        $this->title('Class in 1 hour!')
             ->body(($data['class_name'] ?? '') . ' is starting soon')
             ->type('info')
             ->priority('high')
             ->autoClose(0);
    }
}

namespace App\Notifications\Verticals\TravelSportsFreelancePhotography;

final class ProjectInviteNotification extends BaseMailableNotification
{
    protected string $type = 'freelance.project.invite';
    protected string $template = 'emails.freelance.project_invite';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'New project invite - ₽' . number_format($data['budget'] ?? 0);
    }
}

final class MilestoneApprovedNotification extends BasePushNotification
{
    protected string $type = 'freelance.milestone.approved';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

        $this->title('Milestone approved!')
             ->body('₽' . number_format($data['milestone_amount'] ?? 0) . ' released')
             ->type('success')
             ->deepLink('/projects/' . ($data['project_id'] ?? ''));
    }
}

final class PaymentProcessedNotification extends BaseMailableNotification
{
    protected string $type = 'freelance.payment.processed';
    protected string $template = 'emails.freelance.payment_processed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'database']);
        $this->subject = 'Payment received - ₽' . number_format($data['amount'] ?? 0);
    }
}

namespace App\Notifications\Verticals\TravelSportsFreelancePhotography;

final class SessionBookedNotification extends BaseMailableNotification
{
    protected string $type = 'photography.session.booked';
    protected string $template = 'emails.photography.session_booked';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Photography session confirmed';
    }
}

final class PhotosReadyNotification extends BasePushNotification
{
    protected string $type = 'photography.photos.ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);

        $this->title('Your photos are ready!')
             ->body('View ' . ($data['photo_count'] ?? '0') . ' photos from your session')
             ->type('success')
             ->deepLink('/gallery/' . ($data['session_id'] ?? ''));
    }
}

final class ReviewRequestNotification extends BasePushNotification
{
    protected string $type = 'photography.review.request';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['database', 'push']);

        $this->title('Rate your photographer')
             ->message('Help us improve by sharing your experience')
             ->type('action')
             ->withAction('Review', '/photographers/' . ($data['photographer_id'] ?? '') . '/review', 'primary');
    }
}
