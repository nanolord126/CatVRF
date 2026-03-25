declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * PharmacyOrderCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyOrderCompleted
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly string $correlationId, public readonly mixed $order) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
