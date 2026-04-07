<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerProductResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateFlowerProduct extends CreateRecord
{
    protected static string $resource = FlowerProductResource::class;

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
        return 'Цветочный товар успешно создан';
    }
}
