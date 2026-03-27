<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Events;

use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\SerializesModels;

final /**
 * ServiceJobCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceJobCompleted
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceJob $job, public string $correlationId) {}
}
