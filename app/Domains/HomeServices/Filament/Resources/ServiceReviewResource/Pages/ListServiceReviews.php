declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceReviewResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListServiceReviews
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListServiceReviews extends ListRecords
{
    protected static string $resource = ServiceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
