<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final /**
 * ListBeautyProducts
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListBeautyProducts extends ListRecords
{
    protected static string $resource = BeautyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
