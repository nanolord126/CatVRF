<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources\FlowerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Flowers\Filament\Resources\FlowerResource;

final class ListFlowers extends ListRecords
{
    protected static string $resource = FlowerResource::class;
}
