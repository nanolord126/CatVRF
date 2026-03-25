declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditServiceReview
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditServiceReview extends EditRecord
{
    protected static string $resource = ServiceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
