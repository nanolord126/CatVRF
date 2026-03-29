<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\KidsCenter\Pages;
use App\Filament\Tenant\Resources\KidsCenterResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsKidsCenter extends ListRecords {
    protected static string $resource = KidsCenterResource::class;
}
