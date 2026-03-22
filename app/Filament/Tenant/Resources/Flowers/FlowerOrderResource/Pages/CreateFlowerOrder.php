<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerOrderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFlowerOrder extends CreateRecord
{
    protected static string $resource = FlowerOrderResource::class;
}
