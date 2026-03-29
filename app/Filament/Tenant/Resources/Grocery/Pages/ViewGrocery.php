<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Grocery\Pages;
use App\Filament\Tenant\Resources\GroceryResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordGrocery extends ViewRecord {
    protected static string $resource = GroceryResource::class;
}
