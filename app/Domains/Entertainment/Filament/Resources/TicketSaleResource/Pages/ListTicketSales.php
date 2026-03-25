declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\TicketSaleResource\Pages;

use App\Domains\Entertainment\Filament\Resources\TicketSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListTicketSales
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListTicketSales extends ListRecords
{
    protected static string $resource = TicketSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
