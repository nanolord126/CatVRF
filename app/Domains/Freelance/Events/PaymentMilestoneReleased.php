<?php

declare(strict_types=1);


namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * PaymentMilestoneReleased
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PaymentMilestoneReleased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceContract $contract,
        public readonly float $amount,
        public readonly int $milestoneNumber,
        public readonly string $correlationId,
    ) {}
}
