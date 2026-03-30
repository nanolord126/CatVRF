<?php declare(strict_types=1);

namespace App\Domains\Beauty\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockAlertNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public function __construct(
            private readonly BeautyProduct $product,
            private readonly string $correlationId,
        ) {
        }

        public function via(object $notifiable): array
        {
            return ['mail', 'database'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Низкий остаток расходника')
                ->greeting('Внимание!')
                ->line('Обнаружен низкий остаток по товару/расходнику.')
                ->line('Наименование: ' . (string) $this->product->name)
                ->line('Текущий остаток: ' . (int) $this->product->current_stock)
                ->line('Минимальный порог: ' . (int) ($this->product->min_stock_threshold ?? 0))
                ->line('Correlation ID: ' . $this->correlationId);
        }

        public function toArray(object $notifiable): array
        {
            return [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'current_stock' => (int) $this->product->current_stock,
                'min_threshold' => (int) ($this->product->min_stock_threshold ?? 0),
                'correlation_id' => $this->correlationId,
                'vertical' => 'beauty',
            ];
        }
}
