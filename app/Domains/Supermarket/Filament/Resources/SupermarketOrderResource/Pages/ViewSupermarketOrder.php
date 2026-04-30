<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource\Pages;

use App\Domains\Supermarket\Filament\Resources\SupermarketOrderResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSupermarketOrder extends ViewRecord
{
    protected static string $resource = SupermarketOrderResource::class;
}
