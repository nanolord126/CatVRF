<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts\Pages;

use App\Filament\Tenant\Resources\Gifts\GiftProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListGiftProducts extends ListRecords
{
    protected static string $resource = GiftProductResource::class;
}
