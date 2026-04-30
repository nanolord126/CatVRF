<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Restaurant\Presentation\Resources\OrderResource;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
