<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Flowers\Pages;
use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFlowers extends ListRecords {
    protected static string $resource = FlowersResource::class;
}
