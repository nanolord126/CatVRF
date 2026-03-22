<?php declare(strict_types=1);

namespace App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages;

use App\Domains\Grocery\Filament\Resources\GroceryStoreResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGroceryStore extends CreateRecord
{
    protected static string $resource = GroceryStoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['uuid'] = \Illuminate\Support\Str::uuid();
        $data['correlation_id'] = \Illuminate\Support\Str::uuid();
        return $data;
    }
}
