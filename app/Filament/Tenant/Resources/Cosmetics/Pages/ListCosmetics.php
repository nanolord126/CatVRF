<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Cosmetics\Pages;
use App\Filament\Tenant\Resources\CosmeticsResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsCosmetics extends ListRecords {
    protected static string $resource = CosmeticsResource::class;
}
