<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\SalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\SalonResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateSalon extends CreateRecord
{
    protected static string $resource = SalonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = Str::uuid()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
