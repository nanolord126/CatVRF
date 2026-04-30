<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource;

final class CreateOrderKitchenStatus extends CreateRecord
{
    protected static string $resource = OrderKitchenStatusResource::class;
}
