<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Master\Pages;
use App\Filament\Tenant\Resources\MasterResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsMaster extends ListRecords {
    protected static string $resource = MasterResource::class;
}
