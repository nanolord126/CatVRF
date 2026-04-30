<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources\FlowerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Flowers\Filament\Resources\FlowerResource;

final class CreateFlower extends CreateRecord
{
    protected static string $resource = FlowerResource::class;
}
