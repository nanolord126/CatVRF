declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\CourierResource\Pages;

use App\Filament\Tenant\Resources\Logistics\CourierResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditCourier
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
