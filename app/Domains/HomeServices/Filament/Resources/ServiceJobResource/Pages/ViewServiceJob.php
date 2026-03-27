<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceJobResource;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewServiceJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewServiceJob extends ViewRecord
{
    protected static string $resource = ServiceJobResource::class;
}
