<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use App\Filament\Tenant\Resources\SportingGoods\SportProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListSportProducts extends ListRecords
{
    protected static string $resource = SportProductResource::class;
}
