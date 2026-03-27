<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use App\Filament\Tenant\Resources\Party\PartyCategoryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreatePartyCategory.
 */
final class CreatePartyCategory extends CreateRecord
{
    protected static string $resource = PartyCategoryResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid());
        
        return $data;
    }
}
