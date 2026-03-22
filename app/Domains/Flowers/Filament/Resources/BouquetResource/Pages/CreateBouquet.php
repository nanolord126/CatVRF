<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBouquet extends CreateRecord
{
    protected static string $resource = BouquetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['uuid'] = \Illuminate\Support\Str::uuid();
        $data['correlation_id'] = \Illuminate\Support\Str::uuid();
        return $data;
    }
}
