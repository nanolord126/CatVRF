<?php

declare(strict_types=1);


namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceDeliverable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * DeliverableSubmitted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeliverableSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceDeliverable $deliverable,
        public readonly string $correlationId,
    ) {}
}
