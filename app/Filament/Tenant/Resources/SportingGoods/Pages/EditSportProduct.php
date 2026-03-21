<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use App\Filament\Tenant\Resources\SportingGoods\SportProductResource;
use Filament\Resources\Pages\EditRecord;

final class EditSportProduct extends EditRecord
{
    protected static string $resource = SportProductResource::class;
}
