<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleSalonVerifiedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
