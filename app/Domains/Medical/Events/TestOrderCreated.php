<?php

declare(strict_types=1);


namespace App\Domains\Medical\Events;

use App\Domains\Medical\Models\MedicalTestOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * TestOrderCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TestOrderCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly MedicalTestOrder $testOrder,
        public readonly string $correlationId,
    ) {}
}
