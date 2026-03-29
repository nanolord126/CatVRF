<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Toy\Pages;
use App\Filament\Tenant\Resources\ToyResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsToy extends ListRecords {
    protected static string $resource = ToyResource::class;
}
