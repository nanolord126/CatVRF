<?php

declare(strict_types=1);


namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;

use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateApartment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateApartment extends CreateRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['owner_id'] = auth()->id();
        $data['uuid'] = \Illuminate\Support\Str::uuid();
        $data['correlation_id'] = \Illuminate\Support\Str::uuid();
        return $data;
    }
}
