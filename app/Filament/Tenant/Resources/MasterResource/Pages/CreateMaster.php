<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MasterResource\Pages;

use App\Filament\Tenant\Resources\MasterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();

        return $data;
    }
}
