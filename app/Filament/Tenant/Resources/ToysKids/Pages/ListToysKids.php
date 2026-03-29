<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ToysKids\Pages;
use App\Filament\Tenant\Resources\ToysKidsResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsToysKids extends ListRecords {
    protected static string $resource = ToysKidsResource::class;
}
