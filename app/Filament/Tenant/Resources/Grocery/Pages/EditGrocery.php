<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Grocery\Pages;
use App\Filament\Tenant\Resources\GroceryResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordGrocery extends EditRecord {
    protected static string $resource = GroceryResource::class;
}
