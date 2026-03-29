<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionRetail\Pages;
use App\Filament\Tenant\Resources\FashionRetailResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFashionRetail extends EditRecord {
    protected static string $resource = FashionRetailResource::class;
}
