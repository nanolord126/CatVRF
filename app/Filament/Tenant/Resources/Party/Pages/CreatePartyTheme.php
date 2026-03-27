<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use App\Filament\Tenant\Resources\Party\PartyThemeResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreatePartyTheme.
 */
final class CreatePartyTheme extends CreateRecord
{
    protected static string $resource = PartyThemeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        
        return $data;
    }
}
