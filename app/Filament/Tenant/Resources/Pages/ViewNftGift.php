<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\NftGift\Pages;

use use App\Filament\Tenant\Resources\NftGiftResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewNftGift extends ViewRecord
{
    protected static string $resource = NftGiftResource::class;

    public function getTitle(): string
    {
        return 'View NftGift';
    }
}