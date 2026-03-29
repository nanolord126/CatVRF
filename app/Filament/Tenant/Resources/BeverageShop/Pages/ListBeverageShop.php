<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageShop\Pages;
use App\Filament\Tenant\Resources\BeverageShopResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeverageShop extends ListRecords {
    protected static string $resource = BeverageShopResource::class;
}
