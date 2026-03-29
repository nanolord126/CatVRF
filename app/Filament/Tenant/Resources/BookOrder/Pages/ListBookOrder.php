<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BookOrder\Pages;
use App\Filament\Tenant\Resources\BookOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBookOrder extends ListRecords {
    protected static string $resource = BookOrderResource::class;
}
