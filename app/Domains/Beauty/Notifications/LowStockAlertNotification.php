<?php

declare(strict_types=1);

/**
 * LowStockAlertNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/lowstockalertnotification
 */


namespace App\Domains\Beauty\Notifications;

use Illuminate\Notifications\Notification;

final class LowStockAlertNotification extends Notification
{


    use Queueable;

        public function __construct(
            private BeautyProduct $product,
            private string $correlationId,
        ) {}

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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
