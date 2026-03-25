declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Master;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * MasterRatingUpdated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MasterRatingUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Master $master,
        public readonly float $oldRating,
        public readonly float $newRating,
        public readonly string $correlationId,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
