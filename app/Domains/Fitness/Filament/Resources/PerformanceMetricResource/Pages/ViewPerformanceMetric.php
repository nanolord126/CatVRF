<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\PerformanceMetricResource\Pages;

use App\Domains\Fitness\Filament\Resources\PerformanceMetricResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewPerformanceMetric extends ViewRecord
{
    protected static string $resource = PerformanceMetricResource::class;
}
