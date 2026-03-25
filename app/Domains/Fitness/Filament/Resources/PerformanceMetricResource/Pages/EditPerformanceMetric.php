declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\PerformanceMetricResource\Pages;

use App\Domains\Fitness\Filament\Resources\PerformanceMetricResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditPerformanceMetric
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditPerformanceMetric extends EditRecord
{
    protected static string $resource = PerformanceMetricResource::class;
}
