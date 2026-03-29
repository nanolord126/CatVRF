<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleAuction\Pages;

use use App\Filament\Tenant\Resources\CollectibleAuctionResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCollectibleAuction extends CreateRecord
{
    protected static string $resource = CollectibleAuctionResource::class;

    public function getTitle(): string
    {
        return 'Create CollectibleAuction';
    }
}