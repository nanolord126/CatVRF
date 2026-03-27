<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\SalonVerified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final /**
 * HandleSalonVerifiedListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HandleSalonVerifiedListener implements ShouldQueue
{
    public function handle(SalonVerified $event): void
    {
        $salon = $event->salon;

        // Update salon visibility in search
        app(\App\Services\SearchService::class)->indexSalon($salon);

        // Notify salon owner
        if ($salon->owner) {
            $this->notification->send(
                $salon->owner,
                new \App\Notifications\SalonVerifiedNotification($salon)
            );
        }

        // Clear salon cache
        Cache::forget("salon:{$salon->id}");
        Cache::forget("verified_salons:{$salon->tenant_id}");

        Log::channel('audit')->info('SalonVerified event handled', [
            'salon_id' => $salon->id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
