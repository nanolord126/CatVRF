<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItem\Pages;

use use App\Filament\Tenant\Resources\BeverageItemResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeverageItem extends ViewRecord
{
    protected static string $resource = BeverageItemResource::class;

    public function getTitle(): string
    {
        return 'View BeverageItem';
    }
}