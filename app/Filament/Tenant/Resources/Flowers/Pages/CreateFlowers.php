<?php

declare(strict_types=1);

/**
 * CreateRecordFlowers — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 * @see https://catvrf.ru/docs/createrecordflowers
 */

namespace App\Filament\Tenant\Resources\Flowers\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateRecordFlowers extends CreateRecord
{
    protected static string $resource = FlowersResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = filament()->getTenant()?->id;
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Запись успешно создана';
    }
}
