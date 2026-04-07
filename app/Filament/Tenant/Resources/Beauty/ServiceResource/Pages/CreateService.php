<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = \Illuminate\Support\Str::uuid()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
