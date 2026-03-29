<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Fashion\Pages;
use App\Filament\Tenant\Resources\FashionResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFashion extends ListRecords {
    protected static string $resource = FashionResource::class;
}
