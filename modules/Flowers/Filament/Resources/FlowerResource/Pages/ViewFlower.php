<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources\FlowerResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Flowers\Filament\Resources\FlowerResource;

final class ViewFlower extends ViewRecord
{
    protected static string $resource = FlowerResource::class;
}
