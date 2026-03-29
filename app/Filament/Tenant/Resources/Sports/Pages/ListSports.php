<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Sports\Pages;
use App\Filament\Tenant\Resources\SportsResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsSports extends ListRecords {
    protected static string $resource = SportsResource::class;
}
