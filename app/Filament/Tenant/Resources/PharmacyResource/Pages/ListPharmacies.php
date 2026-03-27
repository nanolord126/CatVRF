<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\PharmacyResource\Pages;

use App\Filament\Tenant\Resources\PharmacyResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListPharmacies
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListPharmacies extends ListRecords
{
    protected static string $resource = PharmacyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
