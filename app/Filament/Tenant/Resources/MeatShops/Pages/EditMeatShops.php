<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatShops\Pages;
use App\Filament\Tenant\Resources\MeatShopsResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordMeatShops extends EditRecord {
    protected static string $resource = MeatShopsResource::class;
}
