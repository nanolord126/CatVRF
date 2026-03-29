<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageItem\Pages;
use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBeverageItem extends CreateRecord {
    protected static string $resource = BeverageItemResource::class;
}
