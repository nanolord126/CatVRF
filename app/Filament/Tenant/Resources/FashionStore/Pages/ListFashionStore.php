<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionStore\Pages;
use App\Filament\Tenant\Resources\FashionStoreResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFashionStore extends ListRecords {
    protected static string $resource = FashionStoreResource::class;
}
