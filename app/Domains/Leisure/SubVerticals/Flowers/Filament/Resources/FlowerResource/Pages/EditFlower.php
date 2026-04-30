<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources\FlowerResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Flowers\Filament\Resources\FlowerResource;

final class EditFlower extends EditRecord
{
    protected static string $resource = FlowerResource::class;
}
