<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatShop\Pages;
use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsMeatShop extends ListRecords {
    protected static string $resource = MeatShopResource::class;
}
