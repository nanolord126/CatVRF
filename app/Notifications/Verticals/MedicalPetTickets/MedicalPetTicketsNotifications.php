<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Medical;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BaseSmsNotification;
use App\Notifications\BasePushNotification;

final class AppointmentScheduledNotification extends BaseMailableNotification
{
    protected string $type = 'medical.appointment.scheduled';
    protected string $template = 'emails.medical.appointment_scheduled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
        $this->subject = 'Appointment confirmed - ' . ($data['doctor_name'] ?? 'Doctor');
    }
}

final class DoctorChangedNotification extends BasePushNotification
{
    protected string $type = 'medical.doctor.changed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Doctor changed')
             ->body('Your appointment doctor is now ' . ($data['new_doctor_name'] ?? ''))
             ->type('warning')
             ->priority('high');
    }
}

final class PrescriptionReadyNotification extends BaseSmsNotification
{
    protected string $type = 'medical.prescription.ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

final class LabResultReadyNotification extends BaseMailableNotification
{
    protected string $type = 'medical.lab_result.ready';
    protected string $template = 'emails.medical.lab_result_ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your lab results are ready';
    }
}

namespace App\Notifications\Verticals\Pet;

final class ServiceBookedNotification extends BaseMailableNotification
{
    protected string $type = 'pet.service.booked';
    protected string $template = 'emails.pet.service_booked';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Pet service booking confirmed';
    }
}

final class ServiceReminderNotification extends BaseSmsNotification
{
    protected string $type = 'pet.service.reminder';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push']);
    }
}

final class ServiceCompletedNotification extends BasePushNotification
{
    protected string $type = 'pet.service.completed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Service completed!')
             ->body('Your pet is ready. Photos: ' . ($data['photo_count'] ?? '0'))
             ->type('success')
             ->autoClose(8000)
             ->deepLink('/pet/' . ($data['pet_id'] ?? '') . '/gallery');
    }
}

namespace App\Notifications\Verticals\Tickets;

final class TicketPurchasedNotification extends BaseMailableNotification
{
    protected string $type = 'tickets.ticket.purchased';
    protected string $template = 'emails.tickets.ticket_purchased';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your tickets - ' . ($data['event_name'] ?? '');
    }
}

final class EventReminderNotification extends BasePushNotification
{
    protected string $type = 'tickets.event.reminder';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Event coming up!')
             ->body(($data['event_name'] ?? '') . ' starts ' . ($data['starts_in'] ?? ''))
             ->type('warning')
             ->autoClose(0)
             ->priority('high')
             ->deepLink('/tickets/' . ($data['ticket_id'] ?? ''));
    }
}

final class EventCancelledNotification extends BaseMailableNotification
{
    protected string $type = 'tickets.event.cancelled';
    protected string $template = 'emails.tickets.event_cancelled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Event cancelled - Refund issued';
    }
}
