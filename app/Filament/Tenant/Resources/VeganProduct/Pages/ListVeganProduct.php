<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\VeganProduct\Pages;
use App\Filament\Tenant\Resources\VeganProductResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsVeganProduct extends ListRecords {
    protected static string $resource = VeganProductResource::class;
}
