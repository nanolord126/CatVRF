<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Filament\Resources\PerformanceMetricResource\Pages;

use App\Domains\Sports\Fitness\Filament\Resources\PerformanceMetricResource;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewPerformanceMetric
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewPerformanceMetric extends ViewRecord
{
    protected static string $resource = PerformanceMetricResource::class;
}
