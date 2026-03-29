<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionStore\Pages;
use App\Filament\Tenant\Resources\FashionStoreResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordFashionStore extends ViewRecord {
    protected static string $resource = FashionStoreResource::class;
}
