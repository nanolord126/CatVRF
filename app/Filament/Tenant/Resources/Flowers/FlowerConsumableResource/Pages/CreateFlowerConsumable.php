<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateFlowerConsumable extends CreateRecord
{
    protected static string $resource = FlowerConsumableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();
        
        return $data;
    }
}
