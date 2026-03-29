<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;
use App\Filament\Tenant\Resources\AutoPartOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsAutoPartOrder extends ListRecords {
    protected static string $resource = AutoPartOrderResource::class;
}
