<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleAuction\Pages;
use App\Filament\Tenant\Resources\CollectibleAuctionResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsCollectibleAuction extends ListRecords {
    protected static string $resource = CollectibleAuctionResource::class;
}
