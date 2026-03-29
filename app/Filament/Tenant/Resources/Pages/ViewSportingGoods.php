<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use use App\Filament\Tenant\Resources\SportingGoodsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewSportingGoods extends ViewRecord
{
    protected static string $resource = SportingGoodsResource::class;

    public function getTitle(): string
    {
        return 'View SportingGoods';
    }
}