<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoSession\Pages;
use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsPhotoSession extends ListRecords {
    protected static string $resource = PhotoSessionResource::class;
}
