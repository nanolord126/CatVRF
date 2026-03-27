<?php

declare(strict_types=1);


namespace App\Domains\Taxi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * SurgeUpdated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SurgeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $oldMultiplier,
        public readonly float $newMultiplier,
        public readonly string $correlationId,
    ) {}
}
