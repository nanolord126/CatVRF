declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\PerformanceMetricResource\Pages;

use App\Domains\Fitness\Filament\Resources\PerformanceMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListPerformanceMetrics
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListPerformanceMetrics extends ListRecords
{
    protected static string $resource = PerformanceMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
