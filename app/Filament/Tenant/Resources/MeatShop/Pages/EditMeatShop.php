<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatShop\Pages;
use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMeatShop extends EditRecord {
    protected static string $resource = MeatShopResource::class;
}
