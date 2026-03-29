<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Stream\Pages;
use App\Filament\Tenant\Resources\StreamResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsStream extends ListRecords {
    protected static string $resource = StreamResource::class;
}
