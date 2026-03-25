declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewBeautySalon
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewBeautySalon extends ViewRecord
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
