<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoPart\Pages;
use App\Filament\Tenant\Resources\AutoPartResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsAutoPart extends ListRecords {
    protected static string $resource = AutoPartResource::class;
}
