<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final /**
 * CreateBeautySalon
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateBeautySalon extends CreateRecord
{
    protected static string $resource = BeautyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();

        if (session()->has('business_card_id')) {
            $data['business_group_id'] = session('business_card_id');
        }

        return $data;
    }
}
