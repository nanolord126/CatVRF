<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrPropertyResource\Pages;

use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateStrProperty extends CreateRecord
{
    protected static string $resource = StrPropertyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = request()->header('X-Correlation-ID', (string) Str::uuid());
        
        return $data;
    }
}
