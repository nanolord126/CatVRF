<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Beauty;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BaseSmsNotification;
use App\Notifications\BaseInAppNotification;

/**
 * Appointment Confirmed Notification - запись на процедуру подтверждена
 */
class AppointmentConfirmedNotification extends BaseMailableNotification
{
    protected string $type = 'beauty.appointment.confirmed';
    protected string $template = 'emails.beauty.appointment_confirmed';

    public function __construct(int $userId, int $tenantId, array $appointmentData)
    {
        parent::__construct($userId, $tenantId, $appointmentData, channels: ['mail', 'sms', 'push', 'database']);
        $this->subject = 'Ваша запись подтверждена';
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'salon_name' => $this->data['salon_name'] ?? null,
            'master_name' => $this->data['master_name'] ?? null,
            'service_name' => $this->data['service_name'] ?? null,
            'appointment_datetime' => $this->data['appointment_datetime'] ?? null,
            'duration_minutes' => $this->data['duration_minutes'] ?? 30,
            'address' => $this->data['address'] ?? null,
        ]);
    }
}

/**
 * Appointment Reminder - напоминание за 24 часа
 */
class AppointmentReminderNotification extends BaseSmsNotification
{
    protected string $type = 'beauty.appointment.reminder.24h';
    protected string $template = 'sms.beauty.reminder_24h';

    public function __construct(int $userId, int $tenantId, array $appointmentData)
    {
        parent::__construct($userId, $tenantId, $appointmentData, channels: ['sms', 'push']);
    }

    public function getSmsText(): string
    {
        $masterName = $this->data['master_name'] ?? 'Мастер';
        $appointmentTime = $this->data['appointment_datetime'] ?? 'завтра';
        return "Напоминаем о записи к $masterName на завтра ($appointmentTime). Салон: {$this->data['salon_name']}.";
    }
}

/**
 * Appointment Final Reminder - напоминание за 2 часа
 */
class AppointmentFinalReminderNotification extends BaseInAppNotification
{
    protected string $type = 'beauty.appointment.reminder.2h';

    public function __construct(int $userId, int $tenantId, array $appointmentData)
    {
        parent::__construct($userId, $tenantId, $appointmentData, channels: ['push', 'database']);
        
        $this->title('Визит в салон через 2 часа')
             ->message("{$appointmentData['master_name']} ждёт вас в {$appointmentData['salon_name']}")
             ->type('warning')
             ->autoClose(10000)
             ->withAction('Показать маршрут', '/salon/' . ($appointmentData['salon_id'] ?? ''));
    }
}

/**
 * Appointment Canceled by Salon - салон отменил запись
 */
class AppointmentCanceledBySalonNotification extends BaseMailableNotification
{
    protected string $type = 'beauty.appointment.canceled_by_salon';
    protected string $template = 'emails.beauty.appointment_canceled';

    public function __construct(int $userId, int $tenantId, array $appointmentData)
    {
        parent::__construct($userId, $tenantId, $appointmentData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Ваша запись отменена';
        $this->priority('high');
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'salon_name' => $this->data['salon_name'] ?? null,
            'cancellation_reason' => $this->data['cancellation_reason'] ?? 'Не указана',
            'refund_status' => $this->data['refund_status'] ?? 'processed',
        ]);
    }
}

/**
 * Review Request - попросить оценку после услуги
 */
class ReviewRequestNotification extends BaseInAppNotification
{
    protected string $type = 'beauty.review_request';

    public function __construct(int $userId, int $tenantId, array $appointmentData)
    {
        parent::__construct($userId, $tenantId, $appointmentData, channels: ['database', 'push']);
        
        $this->title('Помогите другим клиентам')
             ->message("Оцените работу {$appointmentData['master_name']} в {$appointmentData['salon_name']}")
             ->type('info')
             ->autoClose(8000)
             ->withAction('Оставить отзыв', '/review/' . ($appointmentData['appointment_id'] ?? ''))
             ->addFrontendData('service_name', $appointmentData['service_name'] ?? null);
    }
}

/**
 * Promotional Offer from Salon - спецпредложение от салона
 */
class PromoOfferNotification extends BaseMailableNotification
{
    protected string $type = 'beauty.promo_offer';
    protected string $template = 'emails.beauty.promo_offer';

    public function __construct(int $userId, int $tenantId, array $promoData)
    {
        parent::__construct($userId, $tenantId, $promoData, channels: ['mail', 'push', 'database']);
        $this->subject = "Спецпредложение в {$promoData['salon_name']}!";
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'salon_name' => $this->data['salon_name'] ?? null,
            'promo_title' => $this->data['promo_title'] ?? null,
            'discount_percent' => $this->data['discount_percent'] ?? null,
            'valid_until' => $this->data['valid_until'] ?? null,
            'promo_code' => $this->data['promo_code'] ?? null,
        ]);
    }
}
