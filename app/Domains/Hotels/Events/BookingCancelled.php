declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * BookingCancelled
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        public readonly string $reason = '',
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
