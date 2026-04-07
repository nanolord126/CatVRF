<?php declare(strict_types=1);

namespace App\Notifications\Verticals\MedicalPetTickets;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;
use App\Notifications\BaseSmsNotification;

final class AppointmentScheduledNotification extends BaseMailableNotification
{
    private string $type = 'medical.appointment.scheduled';
    private string $template = 'emails.medical.appointment_scheduled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'sms', 'push', 'database']);
        $this->subject = 'Appointment confirmed - ' . ($data['doctor_name'] ?? 'Doctor');
    }
}

final class DoctorChangedNotification extends BasePushNotification
{
    private string $type = 'medical.doctor.changed';

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
    private string $type = 'medical.prescription.ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push', 'database']);
    }
}

final class LabResultReadyNotification extends BaseMailableNotification
{
    private string $type = 'medical.lab_result.ready';
    private string $template = 'emails.medical.lab_result_ready';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your lab results are ready';
    }
}

namespace App\Notifications\Verticals\MedicalPetTickets;

final class ServiceBookedNotification extends BaseMailableNotification
{
    private string $type = 'pet.service.booked';
    private string $template = 'emails.pet.service_booked';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Pet service booking confirmed';
    }
}

final class ServiceReminderNotification extends BaseSmsNotification
{
    private string $type = 'pet.service.reminder';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['sms', 'push']);
    }
}

final class ServiceCompletedNotification extends BasePushNotification
{
    private string $type = 'pet.service.completed';

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

namespace App\Notifications\Verticals\MedicalPetTickets;

final class TicketPurchasedNotification extends BaseMailableNotification
{
    private string $type = 'tickets.ticket.purchased';
    private string $template = 'emails.tickets.ticket_purchased';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your tickets - ' . ($data['event_name'] ?? '');
    }
}

final class EventReminderNotification extends BasePushNotification
{
    private string $type = 'tickets.event.reminder';

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
    private string $type = 'tickets.event.cancelled';
    private string $template = 'emails.tickets.event_cancelled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Event cancelled - Refund issued';
    }
}
