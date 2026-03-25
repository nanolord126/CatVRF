declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListBouquets
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListBouquets extends ListRecords
{
    protected static string $resource = BouquetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->where('tenant_id', tenant()->id);
    }
}
