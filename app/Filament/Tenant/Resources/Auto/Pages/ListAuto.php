<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Auto\Pages;
use App\Filament\Tenant\Resources\AutoResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsAuto extends ListRecords {
    protected static string $resource = AutoResource::class;
}
