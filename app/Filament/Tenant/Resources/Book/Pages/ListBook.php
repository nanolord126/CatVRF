<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Book\Pages;
use App\Filament\Tenant\Resources\BookResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBook extends ListRecords {
    protected static string $resource = BookResource::class;
}
