<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Confectionery\Pages;

use App\Filament\Tenant\Resources\Confectionery\ConfectioneryProductResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListConfectioneryProducts
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListConfectioneryProducts extends ListRecords
{
    protected static string $resource = ConfectioneryProductResource::class;
}
