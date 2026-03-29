<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsCenter\Pages;

use use App\Filament\Tenant\Resources\KidsCenterResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewKidsCenter extends ViewRecord
{
    protected static string $resource = KidsCenterResource::class;

    public function getTitle(): string
    {
        return 'View KidsCenter';
    }
}