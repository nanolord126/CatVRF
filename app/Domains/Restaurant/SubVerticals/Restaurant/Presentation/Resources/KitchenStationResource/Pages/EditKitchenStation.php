<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Restaurant\Presentation\Resources\KitchenStationResource;

final class EditKitchenStation extends EditRecord
{
    protected static string $resource = KitchenStationResource::class;
}
