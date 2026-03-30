<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Hotels;

use App\Notifications\BaseInAppNotification;
use App\Notifications\BaseMailableNotification;

/**
 * Booking Confirmed Notification - бронирование подтверждено
 */
class BookingConfirmedNotification extends BaseMailableNotification
{
    protected string $type = 'hotels.booking.confirmed';
    protected string $template = 'emails.hotels.booking_confirmed';

    public function __construct(int $userId, int $tenantId, array $bookingData)
    {
        parent::__construct($userId, $tenantId, $bookingData, channels: ['mail', 'push', 'database']);
        $this->subject = 'Бронирование подтверждено';
    }

    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'hotel_name' => $this->data['hotel_name'] ?? null,
            'room_type' => $this->data['room_type'] ?? null,
            'check_in_date' => $this->data['check_in_date'] ?? null,
            'check_out_date' => $this->data['check_out_date'] ?? null,
            'booking_number' => $this->data['booking_number'] ?? null,
            'total_price' => $this->data['total_price'] ?? 0,
        ]);
    }
}

/**
 * Check-in Reminder - напоминание перед заселением
 */
class CheckInReminderNotification extends BaseInAppNotification
{
    protected string $type = 'hotels.check_in.reminder';

    public function __construct(int $userId, int $tenantId, array $bookingData)
    {
        parent::__construct($userId, $tenantId, $bookingData, channels: ['push', 'database']);

        $this->title('Завтра вы приезжаете в ' . ($bookingData['hotel_name'] ?? 'отель'))
             ->message('Не забудьте свой паспорт и бронь-номер ' . ($bookingData['booking_number'] ?? ''))
             ->type('info')
             ->autoClose(10000)
             ->withAction('Показать детали', '/booking/' . ($bookingData['booking_id'] ?? ''));
    }
}

/**
 * Payout Processed - выплата отправлена (для отелей)
 */
class PayoutProcessedNotification extends BaseMailableNotification
{
    protected string $type = 'hotels.payout.processed';
    protected string $template = 'emails.hotels.payout_processed';

    public function __construct(int $userId, int $tenantId, array $payoutData)
    {
        parent::__construct($userId, $tenantId, $payoutData, channels: ['mail', 'database']);
        $this->subject = 'Выплата проведена';
    }
}

/**
 * Review Request - просьба оставить отзыв
 */
class ReviewRequestNotification extends BaseInAppNotification
{
    protected string $type = 'hotels.review_request';

    public function __construct(int $userId, int $tenantId, array $bookingData)
    {
        parent::__construct($userId, $tenantId, $bookingData, channels: ['database', 'push']);

        $this->title('Спасибо за остановку!')
             ->message('Поделитесь впечатлениями о ' . ($bookingData['hotel_name'] ?? 'отеле'))
             ->type('success')
             ->autoClose(8000)
             ->withAction('Оставить отзыв', '/review/hotel/' . ($bookingData['hotel_id'] ?? ''));
    }
}
