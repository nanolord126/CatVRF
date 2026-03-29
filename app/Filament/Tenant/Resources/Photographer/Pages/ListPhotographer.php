<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Photographer\Pages;
use App\Filament\Tenant\Resources\PhotographerResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsPhotographer extends ListRecords {
    protected static string $resource = PhotographerResource::class;
}
