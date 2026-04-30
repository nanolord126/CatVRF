<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupermarketOrderResource\Pages;

use App\Filament\Resources\SupermarketOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateSupermarketOrder extends CreateRecord
{
    protected static string $resource = SupermarketOrderResource::class;
}
