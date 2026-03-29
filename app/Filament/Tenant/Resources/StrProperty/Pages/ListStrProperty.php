<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StrProperty\Pages;
use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsStrProperty extends ListRecords {
    protected static string $resource = StrPropertyResource::class;
}
