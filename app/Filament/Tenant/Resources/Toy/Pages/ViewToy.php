<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Toy\Pages;
use App\Filament\Tenant\Resources\ToyResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordToy extends ViewRecord {
    protected static string $resource = ToyResource::class;
}
