<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleServiceCreatedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(ServiceCreated $event): void
        {
            $service = $event->service;

            // Invalidate services cache for salon
            if ($service->salon_id) {
                Cache::forget("salon_services:{$service->salon_id}");
            }

            // Update search index
            app(\App\Services\SearchService::class)->indexService($service);

            Log::channel('audit')->info('ServiceCreated event handled', [
                'service_id' => $service->id,
                'salon_id' => $service->salon_id,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
