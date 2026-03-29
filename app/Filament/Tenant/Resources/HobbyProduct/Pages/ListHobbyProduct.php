<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\HobbyProduct\Pages;
use App\Filament\Tenant\Resources\HobbyProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsHobbyProduct extends ListRecords {
    protected static string $resource = HobbyProductResource::class;
}
