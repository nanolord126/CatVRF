<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

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
