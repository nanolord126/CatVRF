<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\SalonResource\Pages;

use App\Domains\Beauty\DTOs\CreateSalonDto;
use App\Domains\Beauty\Services\SalonService;
use App\Filament\Tenant\Resources\Beauty\SalonResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSalon extends CreateRecord
{
    protected static string $resource = SalonResource::class;

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
