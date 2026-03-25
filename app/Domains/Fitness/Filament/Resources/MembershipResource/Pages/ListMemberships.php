declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\MembershipResource\Pages;

use App\Domains\Fitness\Filament\Resources\MembershipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListMemberships
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
