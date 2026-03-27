<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final /**
 * ListBeautySalons
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
