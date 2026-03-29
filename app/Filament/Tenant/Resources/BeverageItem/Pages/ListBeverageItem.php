<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageItem\Pages;
use App\Filament\Tenant\Resources\BeverageItemResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeverageItem extends ListRecords {
    protected static string $resource = BeverageItemResource::class;
}
