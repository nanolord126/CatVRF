<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ToyOrder\Pages;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsToyOrder extends ListRecords {
    protected static string $resource = ToyOrderResource::class;
}
