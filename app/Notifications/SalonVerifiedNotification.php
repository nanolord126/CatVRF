<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonVerifiedNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public function __construct(
            private readonly BeautySalon $salon,
        ) {
        /**
         * Инициализировать класс
         */
        public function __construct()
        {
            // TODO: инициализация
        }
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

        public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
        {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Салон верифицирован')
                ->line('Поздравляем! Ваш салон успешно прошёл верификацию.')
                ->line('Салон: ' . $this->salon->name)
                ->line('Теперь вы можете принимать записи от клиентов.')
                ->action('Перейти в панель управления', url('/tenant/dashboard'));
        }
}
