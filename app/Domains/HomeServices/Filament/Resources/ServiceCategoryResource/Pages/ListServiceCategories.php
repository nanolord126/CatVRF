<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListServiceCategories
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListServiceCategories extends ListRecords
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
