<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\BouquetResource\Pages;

use App\Filament\Tenant\Resources\Flowers\BouquetResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBouquet extends CreateRecord
{
    protected static string $resource = BouquetResource::class;
}
