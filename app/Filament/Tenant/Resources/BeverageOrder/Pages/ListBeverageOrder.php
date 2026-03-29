<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageOrder\Pages;
use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeverageOrder extends ListRecords {
    protected static string $resource = BeverageOrderResource::class;
}
