<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Order\Pages;

use use App\Filament\Tenant\Resources\OrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'View Order';
    }
}