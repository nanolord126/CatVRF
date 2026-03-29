<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleAuction\Pages;

use use App\Filament\Tenant\Resources\CollectibleAuctionResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewCollectibleAuction extends ViewRecord
{
    protected static string $resource = CollectibleAuctionResource::class;

    public function getTitle(): string
    {
        return 'View CollectibleAuction';
    }
}