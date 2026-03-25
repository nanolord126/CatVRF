declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use App\Domains\Hotels\Models\Review;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ReviewSubmitted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReviewSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Review $review,
        public readonly string $correlationId = '',
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
