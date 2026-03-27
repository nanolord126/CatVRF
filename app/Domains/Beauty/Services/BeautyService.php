<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\BeautyService as BeautyServiceModel;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Beauty Service (Layer 3)
 * Управление услугами салонов красоты.
 */
final readonly class BeautyService
{
    public function __construct(
        private FraudControlService $fraudControl,
    ) {}

    /**
     * Создать новую услугу.
     */
    public function createService(array $data, string $correlationId = null): BeautyServiceModel
    {
        $correlationId ??= (string) Str::uuid();

        return DB::transaction(function () use ($data, $correlationId) {
            // Исправленная сигнатура FraudControlService::check
            $this->fraudControl->check(
                userId: (int) (auth()->id() ?? 0),
                operationType: 'create_beauty_service',
                amount: (int) ($data['price'] ?? 0),
                correlationId: $correlationId
            );

            $service = BeautyServiceModel::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Beauty service created', [
                'service_id' => $service->id,
                'name' => $service->name,
                'correlation_id' => $correlationId,
            ]);

            return $service;
        });
    }

    /**
     * Обновить существующую услугу.
     */
    public function updateService(BeautyServiceModel $service, array $data, string $correlationId = null): BeautyServiceModel
    {
        $correlationId ??= $service->correlation_id ?? (string) Str::uuid();

        return DB::transaction(function () use ($service, $data, $correlationId) {
            $this->fraudControl->check(
                userId: (int) (auth()->id() ?? 0),
                operationType: 'update_beauty_service',
                amount: (int) ($data['price'] ?? $service->price),
                correlationId: $correlationId
            );

            $service->update(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Beauty service updated', [
                'service_id' => $service->id,
                'correlation_id' => $correlationId,
            ]);

            return $service;
        });
    }

    /**
     * Получить все услуги салона.
     */
    public function getSalonServices(int $salonId): Collection
    {
        return BeautyServiceModel::where('salon_id', $salonId)->get();
    }

    /**
     * Удалить услугу (Soft Delete).
     */
    public function deleteService(BeautyServiceModel $service, string $correlationId = null): bool
    {
        $correlationId ??= (string) Str::uuid();

        return DB::transaction(function () use ($service, $correlationId) {
            $this->fraudControl->check(
                userId: (int) (auth()->id() ?? 0),
                operationType: 'delete_beauty_service',
                amount: 0,
                correlationId: $correlationId
            );

            $result = $service->delete();

            Log::channel('audit')->info('Beauty service deleted', [
                'service_id' => $service->id,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }
}
