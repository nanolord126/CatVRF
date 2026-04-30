<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Restaurant\Presentation\Resources\KitchenStationResource;

final class CreateKitchenStation extends CreateRecord
{
    protected static string $resource = KitchenStationResource::class;
}
