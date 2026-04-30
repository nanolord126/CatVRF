<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAdInventory extends EditRecord
{
    protected static string $resource = AdInventoryResource::class;
}
