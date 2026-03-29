<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionProduct\Pages;
use App\Filament\Tenant\Resources\FashionProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFashionProduct extends EditRecord {
    protected static string $resource = FashionProductResource::class;
}
