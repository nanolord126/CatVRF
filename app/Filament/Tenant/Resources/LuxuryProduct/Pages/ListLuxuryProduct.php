<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\LuxuryProduct\Pages;
use App\Filament\Tenant\Resources\LuxuryProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsLuxuryProduct extends ListRecords {
    protected static string $resource = LuxuryProductResource::class;
}
