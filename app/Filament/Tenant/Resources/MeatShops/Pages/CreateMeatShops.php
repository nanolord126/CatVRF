<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatShops\Pages;
use App\Filament\Tenant\Resources\MeatShopsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordMeatShops extends CreateRecord {
    protected static string $resource = MeatShopsResource::class;
}
