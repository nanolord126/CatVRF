<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource;

final class EditOrderKitchenStatus extends EditRecord
{
    protected static string $resource = OrderKitchenStatusResource::class;
}
