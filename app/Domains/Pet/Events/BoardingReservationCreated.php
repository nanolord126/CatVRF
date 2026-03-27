<?php

declare(strict_types=1);


namespace App\Domains\Pet\Events;

use App\Domains\Pet\Models\PetBoardingReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * BoardingReservationCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BoardingReservationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PetBoardingReservation $reservation,
        public readonly string $correlationId,
    ) {}
}
