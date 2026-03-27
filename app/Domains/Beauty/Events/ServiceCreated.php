<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Service;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ServiceCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceCreated
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Service $service,
        public readonly string $correlationId,
    ) {}
}
