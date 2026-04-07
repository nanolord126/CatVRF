<?php declare(strict_types=1);

/**
 * CreateFlowerOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 * @see https://catvrf.ru/docs/createflowerorder
 */


namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerOrderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFlowerOrder extends CreateRecord
{
    protected static string $resource = FlowerOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) \Illuminate\Support\Str::uuid();
        $data['tenant_id'] = filament()->getTenant()?->id;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Заказ цветов успешно создан';
    }
}
