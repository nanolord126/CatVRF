declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainerResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListEntertainers
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListEntertainers extends ListRecords
{
    protected static string $resource = EntertainerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
