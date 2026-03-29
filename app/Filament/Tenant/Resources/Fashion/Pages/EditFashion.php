<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Fashion\Pages;
use App\Filament\Tenant\Resources\FashionResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFashion extends EditRecord {
    protected static string $resource = FashionResource::class;
}
