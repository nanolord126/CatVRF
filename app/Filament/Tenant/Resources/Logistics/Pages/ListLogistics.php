<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Logistics\Pages;
use App\Filament\Tenant\Resources\LogisticsResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsLogistics extends ListRecords {
    protected static string $resource = LogisticsResource::class;
}
