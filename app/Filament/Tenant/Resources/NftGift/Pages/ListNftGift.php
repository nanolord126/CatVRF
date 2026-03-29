<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\NftGift\Pages;
use App\Filament\Tenant\Resources\NftGiftResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsNftGift extends ListRecords {
    protected static string $resource = NftGiftResource::class;
}
