<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageItem\Pages;
use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBeverageItem extends EditRecord {
    protected static string $resource = BeverageItemResource::class;
}
