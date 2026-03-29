<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionStore\Pages;
use App\Filament\Tenant\Resources\FashionStoreResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFashionStore extends EditRecord {
    protected static string $resource = FashionStoreResource::class;
}
