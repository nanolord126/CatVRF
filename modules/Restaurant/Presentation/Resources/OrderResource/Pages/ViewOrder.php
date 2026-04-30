<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Restaurant\Presentation\Resources\OrderResource;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
