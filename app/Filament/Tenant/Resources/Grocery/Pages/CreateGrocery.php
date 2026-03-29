<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Grocery\Pages;
use App\Filament\Tenant\Resources\GroceryResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordGrocery extends CreateRecord {
    protected static string $resource = GroceryResource::class;
}
