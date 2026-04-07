<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Domains\Beauty\Models\Salon as BeautySalon;

/**
 * Class SalonVerifiedNotification
 *
 * Уведомление об успешной верификации салона красоты.
 * Отправляется владельцу салона по database и email каналам.
 *
 * @package App\Notifications
 */
final class SalonVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly BeautySalon $salon,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'salon_id' => $this->salon->id,
            'salon_name' => $this->salon->name,
            'message' => 'Ваш салон успешно верифицирован',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Салон верифицирован')
            ->line('Поздравляем! Ваш салон успешно прошёл верификацию.')
            ->line('Салон: ' . $this->salon->name)
            ->line('Теперь вы можете принимать записи от клиентов.')
            ->action('Перейти в панель управления', url('/tenant/dashboard'));
    }
}
