<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdInventory extends CreateRecord
{
    protected static string $resource = AdInventoryResource::class;
}
