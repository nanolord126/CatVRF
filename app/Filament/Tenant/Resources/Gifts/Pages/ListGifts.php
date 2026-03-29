<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Gifts\Pages;
use App\Filament\Tenant\Resources\GiftsResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsGifts extends ListRecords {
    protected static string $resource = GiftsResource::class;
}
