<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BakeryOrder\Pages;
use App\Filament\Tenant\Resources\BakeryOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBakeryOrder extends ListRecords {
    protected static string $resource = BakeryOrderResource::class;
}
