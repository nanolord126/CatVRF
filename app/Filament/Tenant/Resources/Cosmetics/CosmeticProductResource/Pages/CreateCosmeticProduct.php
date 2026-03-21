<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource\Pages;

use App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCosmeticProduct extends CreateRecord
{
    protected static string $resource = CosmeticProductResource::class;
}
