declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrmResource\Pages;

use App\Filament\Tenant\Resources\UserCrmResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final /**
 * ListUserCrm
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListUserCrm extends ListRecords
{
    protected static string $resource = UserCrmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Экспорт всех')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
