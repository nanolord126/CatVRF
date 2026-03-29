<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleAuction\Pages;
use App\Filament\Tenant\Resources\CollectibleAuctionResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordCollectibleAuction extends EditRecord {
    protected static string $resource = CollectibleAuctionResource::class;
}
