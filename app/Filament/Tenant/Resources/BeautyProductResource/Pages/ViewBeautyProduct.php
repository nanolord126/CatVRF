declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewBeautyProduct
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewBeautyProduct extends ViewRecord
{
    protected static string $resource = BeautyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
