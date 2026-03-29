<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageShop\Pages;
use App\Filament\Tenant\Resources\BeverageShopResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBeverageShop extends CreateRecord {
    protected static string $resource = BeverageShopResource::class;
}
