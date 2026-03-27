<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Flowers\BouquetResource\Pages;

use App\Filament\Tenant\Resources\Flowers\BouquetResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateBouquet
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateBouquet extends CreateRecord
{
    protected static string $resource = BouquetResource::class;
}
