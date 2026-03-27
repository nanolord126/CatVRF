<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceJobResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListServiceJobs
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListServiceJobs extends ListRecords
{
    protected static string $resource = ServiceJobResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
