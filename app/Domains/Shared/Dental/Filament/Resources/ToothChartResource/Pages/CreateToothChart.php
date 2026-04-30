<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\ToothChartResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Dental\Filament\Resources\ToothChartResource;

final class CreateToothChart extends CreateRecord
{
    protected static string $resource = ToothChartResource::class;
}
