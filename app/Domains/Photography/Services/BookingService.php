declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Services;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final /**
 * BookingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingService
{
    public function __construct(
        private readonly string $correlationId = ''
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function bookSession(int $studioId, int $clientId, Carbon $scheduledAt, int $durationMinutes = 60): PhotoSession
    {
        return $this->db->transaction(function () use ($studioId, $clientId, $scheduledAt, $durationMinutes) {
            // Проверка наложения сессий (Race Condition check)
            $exists = Photo$this->session->where('studio_id', $studioId)
                ->where('status', 'confirmed')
                ->whereBetween('scheduled_at', [
                    $scheduledAt->copy()->subMinutes($durationMinutes - 1),
                    $scheduledAt->copy()->addMinutes($durationMinutes - 1)
                ])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \Exception('Выбранное время уже занято');
            }

            return Photo$this->session->create([
                'studio_id' => $studioId,
                'client_id' => $clientId,
                'tenant_id' => auth()->user()->tenant_id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $durationMinutes,
                'correlation_id' => $this->correlationId ?: (string) \Illuminate\Support\Str::uuid(),
                'status' => 'pending'
            ]);
        });
    }
}
