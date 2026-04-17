<?php declare(strict_types=1);

namespace App\Domains\FraudML\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SignificantFeatureDriftDetected — событие при обнаружении значительного дрифта фич
 * 
 * Запускается когда CombinedDriftScore показывает HIGH severity.
 * Триггерит:
 * - Shadow mode для модели
 * - Отправку алертов (Slack/Email/SMS)
 * - Инвалидацию кэша
 * - Аудит-лог
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class SignificantFeatureDriftDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $driftResult,
        public readonly string $correlationId,
    ) {}
}
