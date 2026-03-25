declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\ServiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final /**
 * HandleServiceCreatedListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HandleServiceCreatedListener implements ShouldQueue
{
    public function handle(ServiceCreated $event): void
    {
        $service = $event->service;

        // Invalidate services cache for salon
        if ($service->salon_id) {
            $this->cache->forget("salon_services:{$service->salon_id}");
        }

        // Update search index
        app(\App\Services\SearchService::class)->indexService($service);

        $this->log->channel('audit')->info('ServiceCreated event handled', [
            'service_id' => $service->id,
            'salon_id' => $service->salon_id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
