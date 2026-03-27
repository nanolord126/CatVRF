<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final /**
 * CreateBeautyProduct
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateBeautyProduct extends CreateRecord
{
    protected static string $resource = BeautyProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();
        return $data;
    }
}
