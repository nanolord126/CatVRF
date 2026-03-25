declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * PharmacyCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly string $correlationId, public readonly mixed $pharmacy) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
