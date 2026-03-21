<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use App\Filament\Tenant\Resources\SportingGoods\SportProductResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSportProduct extends CreateRecord
{
    protected static string $resource = SportProductResource::class;
}
