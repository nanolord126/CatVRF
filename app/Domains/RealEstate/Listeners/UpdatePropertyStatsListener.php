<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdatePropertyStatsListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(PropertyViewed $event): void
        {
            try {
                $property = $event->appointment->property;

                // Инкрементируем счётчик просмотров
                $property->increment('view_count');

                Log::channel('audit')->info('Property stats updated', [
                    'property_id' => $property->id,
                    'view_count' => $property->view_count,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to update property stats', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
