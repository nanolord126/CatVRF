declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * WarrantyClaimSubmitted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class WarrantyClaimSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $warrantyClaimId,
        public readonly int $tenantId,
        public readonly int $userId,
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
